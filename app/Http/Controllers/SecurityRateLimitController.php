<?php

namespace App\Http\Controllers;


use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
use App\Http\Resources\Tag\TagResource;
use App\Models\SecurityRateLimit;
use App\Models\Tag;
use App\Models\TokenPoint;
use App\Models\User;
use App\Repository\Eloquent\TagRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use phpDocumentor\Reflection\Types\Null_;

class SecurityRateLimitController extends Controller
{

    public function index(Request $request)
    {
        $result = [
            'user_id' => [],
            'ip_address' => [],
            'route' => [],
        ];

        $filters = $request->get('filters', []);
        $ipAddressFilter = Arr::get($filters, 'ip_address');
        $userIdFilter = intval(Arr::get($filters, 'user_id'));
        $routeFilter = Arr::get($filters, 'route');
        $dateFilter = Carbon::parse(Arr::get($filters, 'date')? Arr::get($filters, 'date') : Carbon::now() )->format('Y-m-d');

        $match = [];

        if (!empty($userIdFilter)){
            $match['user_id'] = $userIdFilter;
        }

        if (!empty($ipAddressFilter)){
            $match['ip_address'] = $ipAddressFilter;
        }

        if (!empty($routeFilter)){
            $match['route'] = $routeFilter;
        }

        $aggregateUserId = [];
        $aggregateIpAddress = [];
        $aggregateRoute = [];

        if (!empty($match)){
            $aggregateUserId[] = ['$match' => $match];
            $aggregateIpAddress[] = ['$match' => $match];
            $aggregateRoute[] = ['$match' => $match];
        }

        $aggregateUserId[] = ['$group' => ['_id' => '$user_id', 'count' => ['$sum' => 1],]];
        $aggregateIpAddress[] = ['$group' => ['_id' => '$ip_address', 'count' => ['$sum' => 1],]];
        $aggregateRoute[] = ['$group' => ['_id' => '$route', 'count' => ['$sum' => 1],]];

        $aggregateUserId[] = ['$sort' => ['count' => -1]];
        $aggregateIpAddress[] = ['$sort' => ['count' => -1]];
        $aggregateRoute[] = ['$sort' => ['count' => -1]];


        $result['user_id'] = (new SecurityRateLimit())
            ->setCollection("rate_limit_{$dateFilter}")
            ->raw(function($collection) use ($aggregateUserId){
                return $collection->aggregate($aggregateUserId);
            });

        $result['ip_address'] = (new SecurityRateLimit())
            ->setCollection("rate_limit_{$dateFilter}")
            ->raw(function($collection) use ($aggregateIpAddress){
                return $collection->aggregate($aggregateIpAddress);
            });

        $result['route'] = (new SecurityRateLimit())
            ->setCollection("rate_limit_{$dateFilter}")
            ->raw(function($collection) use ($aggregateRoute){
                return $collection->aggregate($aggregateRoute);
            });
        return response()->json($result);
    }

    public function restoreBlockedTokens()
    {
        $rows = (new SecurityRateLimit())->setCollection('rate_limit_2023-11-18')->raw(function($collection){
            return $collection->aggregate([
                ['$group' => [
                    '_id' => '$user_id',
                    'count' => [
                        '$sum' => 1
                    ],
                ]],
                ['$match' => [
                    'count' => ['$gte'=> 100, '$lte'=> 300],
                ]],
                ['$sort' => ['count' => -1]],
            ]);
        })->pluck('_id')->toArray();

        $userIds = array_map(function($user_id){ return intval($user_id); }, $rows);

        TokenPoint::whereIn('user_id', $userIds)->whereNotNull('claimable_by')->update(['claimable_at' => null, 'claimable_by' => null]);

        return response()->json(['status' => 'ok']);

        $userId = 66847;

        TokenPoint::where('user_id', $userId)->whereNotNull('claimable_by')->update(['claimable_at' => null, 'claimable_by' => null]);

        Cache::forget("\App\Http\Controllers\VideoController@watch_time_store.ip2400:9800:390:2232:1353:ea75:470c:ed11.block");
    }

    public function disableUsers()
    {
        $userIds = [62290, 62292];
        $userIds = array_merge($userIds, User::whereIn('referrer_id', $userIds)->pluck('id')->toArray());

        $userIds = User::whereIn('referrer_id', $userIds)->pluck('id')->toArray();

        /*$ipAddresses = User::whereIn('id', $userIds)->pluck('registration_ip')->toArray();
        $ipAddresses = array_filter($ipAddresses);
        foreach ($ipAddresses as $ipAddress){
            Cache::put("\App\Http\Controllers\VideoController@watch_time_store.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
            Cache::put("\App\Http\Controllers\Auth\LoginController@loginWithWallet.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
        }*/

        TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime(Carbon::now()), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds)->update(['status' => User::STATUS_INACTIVE]);

        /*$ipAddress = '45.87.252.153';
        $userIds = User::where('last_active_from_ip', $ipAddress)->pluck('id')->toArray();
        TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime(Carbon::now()), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds)->update(['status' => User::STATUS_INACTIVE]);

        Cache::put("\App\Http\Controllers\VideoController@watch_time_store.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
        Cache::put("\App\Http\Controllers\Auth\LoginController@loginWithWallet.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
        Cache::put("\App\Http\Controllers\Auth\RegisterController@register.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));


        $userIds2 = User::whereIn('referrer_id', $userIds)->pluck('id')->toArray();
        TokenPoint::whereIn('user_id', $userIds2)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime(Carbon::now()), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds2)->update(['status' => User::STATUS_INACTIVE]);*/


        return response()->json(['user_ids'=>$userIds, /*'user_ids2'=>$userIds2, */'tokens' => TokenPoint::whereIn('user_id', $userIds)->whereNotNull('claimable_by')->get()]);
    }
}
