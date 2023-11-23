<?php

namespace App\Http\Controllers;


use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
use App\Http\Resources\Tag\TagResource;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\WatchTime\WatchTimeResource;
use App\Models\SecurityRateLimit;
use App\Models\Tag;
use App\Models\TokenPoint;
use App\Models\User;
use App\Models\WatchTime;
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
            'total' => 0,
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

        $aggregateUserId[] = ['$project' => ['user_id' => '$user_id','blocked' => ['$cond' => ['$is_blocked', 1, 0] ],]];
        $aggregateIpAddress[] = ['$project' => ['ip_address' => '$ip_address','blocked' => ['$cond' => ['$is_blocked', 1, 0] ],]];
        $aggregateRoute[] = ['$project' => ['route' => '$route','blocked' => ['$cond' => ['$is_blocked', 1, 0] ],]];


        $aggregateUserId[] = ['$group' => ['_id' => '$user_id', 'count_all' => ['$sum' => 1], 'count_blocked' => ['$sum' => '$blocked'],]];
        $aggregateIpAddress[] = ['$group' => ['_id' => '$ip_address', 'count_all' => ['$sum' => 1], 'count_blocked' => ['$sum' => '$blocked'],]];
        $aggregateRoute[] = ['$group' => ['_id' => '$route', 'count_all' => ['$sum' => 1], 'count_blocked' => ['$sum' => '$blocked'],]];

        $aggregateUserId[] = ['$sort' => ['count_all' => -1]];
        $aggregateIpAddress[] = ['$sort' => ['count_all' => -1]];
        $aggregateRoute[] = ['$sort' => ['count_all' => -1]];


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

        $result['total'] = (new SecurityRateLimit())
            ->setCollection("rate_limit_{$dateFilter}")->count();

        $result['suspicious_ips'] = (new SecurityRateLimit())
            ->setCollection("rate_limit_{$dateFilter}")->raw(function($collection){
                return $collection->aggregate([
                    ['$group' => ['_id' => ['ip_address' => '$ip_address', 'user_id' => '$user_id'],]],
                    ['$group' => ['_id' => '$_id.ip_address',"users_count" => ['$sum' => 1] ]],
                    ['$sort' => ['users_count' => -1]],
                    ['$match' => ['users_count' => ['$gte'=> 2],]],
                ]);
            })->pluck('users_count','_id')->toArray();

        return response()->json($result);
    }

    public function restoreBlockedTokens()
    {
        (new SecurityRateLimit())->setCollection('rate_limit_2023-11-18')->update(["is_blocked" => true]);
        (new SecurityRateLimit())->setCollection('rate_limit_2023-11-19')->update(["is_blocked" => true]);
        (new SecurityRateLimit())->setCollection('rate_limit_2023-11-20')->update(["is_blocked" => true]);
        (new SecurityRateLimit())->setCollection('rate_limit_2023-11-21')->update(["is_blocked" => true]);
        SecurityRateLimit::where("created_at", '<=' , Carbon::parse('2023-11-22 14:20:00'))->update(["is_blocked" => true]);

        return response()->json(['status' => 'ok']);

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
        $userIds = [74999,75001,75002,75003,75008,75012,75013,75014,63310,63311,63971, 63989,73124,73148,64128,64122,28670,28788,64906,67444,72941,72965
        ,64128,64122,63336,63442,74356,74526,63417,63414,75298,75300,73601,73664,74771,74825,74835,74798,74838,74852,75042,75055,75058,75061,75064,75077,75101,75125
        ,75189,75191,75194,75196,75198,75202,75205];
        $userIds = array_merge($userIds, User::whereIn('referrer_id', $userIds)->pluck('id')->toArray());

        $userIds = array_merge($userIds, User::whereIn('referrer_id', $userIds)->pluck('id')->toArray());

        $ipAddresses = User::whereIn('id', $userIds)->pluck('registration_ip')->toArray();
        $ipAddresses = array_filter($ipAddresses);
        foreach ($ipAddresses as $ipAddress){
            Cache::put("\App\Http\Controllers\VideoController@watch_time_store.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
            Cache::put("\App\Http\Controllers\Auth\LoginController@loginWithWallet.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
            Cache::put("\App\Http\Controllers\Auth\RegisterController@register.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
        }

        TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime(Carbon::now()), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds)->update(['status' => User::STATUS_INACTIVE]);

        $users = User::whereIn('id', $userIds)->get(['username','email', 'auth_wallet', 'registration_ip']);
        /*$ipAddress = '176.233.51.78';
        $userIds = User::where('registration_ip', $ipAddress)->pluck('id')->toArray();
        TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime(Carbon::now()), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds)->update(['status' => User::STATUS_INACTIVE]);

        Cache::put("\App\Http\Controllers\VideoController@watch_time_store.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
        Cache::put("\App\Http\Controllers\Auth\LoginController@loginWithWallet.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
        Cache::put("\App\Http\Controllers\Auth\RegisterController@register.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));


        $userIds2 = User::whereIn('referrer_id', $userIds)->pluck('id')->toArray();
        TokenPoint::whereIn('user_id', $userIds2)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime(Carbon::now()), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds2)->update(['status' => User::STATUS_INACTIVE]);*/


        return response()->json(['user_ids'=>$users]);
    }

    public function usersInfo(Request $request)
    {
        $filters = $request->get('filters', []);
        $userIds = array_filter(explode(',', Arr::get($filters, 'user_ids')));
        $ipAddress = Arr::get($filters, 'ip_address');
        $date = Arr::get($filters, 'date');

        $query = User::whereIn('id', $userIds)->whereNotNull('email_verified_at');

        if ($ipAddress && $date){
            $query->orWhere(function ($query) use ($ipAddress, $date){
                $query->whereDate('created_at', Carbon::parse($date))->where('registration_ip', $ipAddress);
            });
        }

        $users = $query->get();

        $users->append(['auth_wallet', 'status_text', 'registration_ip', 'last_active_from_ip'])->load(['referrer']);

        foreach ($users as $user){
            $user->referrer && $user->referrer->append(['auth_wallet', 'status_text', 'registration_ip', 'last_active_from_ip']);
        }

        return UserResource::collection($users);
    }

    public function blockUsers(Request $request)
    {
        $request->validate([
            'user_ids' => ['required']
        ]);

        $userIds = $request->get('user_ids');

        $ipAddresses = User::whereIn('id', $userIds)->pluck('last_active_from_ip')->toArray();
        foreach ($ipAddresses as $ipAddress){
            Cache::put("\App\Http\Controllers\VideoController@watch_time_store.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
            Cache::put("\App\Http\Controllers\Auth\LoginController@loginWithWallet.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
            Cache::put("\App\Http\Controllers\Auth\RegisterController@register.ip{$ipAddress}.block", true, Carbon::now()->addDays(7));
        }

        TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime(Carbon::now()), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds)->update(['status' => User::STATUS_INACTIVE]);

        return response()->json(['status'=> 'ok']);
    }

    public function userReferrals(Request $request, $userId)
    {
        $perPage = $request->get('per_page') ?: 15;

        $users = User::where('referrer_id', $userId)->paginate($perPage);

        $users->append(['auth_wallet', 'status_text', 'registration_ip', 'last_active_from_ip']);

        return UserResource::collection($users);
    }

    public function userWatchTimes(Request $request, $userId)
    {
        $perPage = $request->get('per_page') ?: 15;

        $watchTimes = WatchTime::where('user_id', $userId)->paginate($perPage);

        return WatchTimeResource::collection($watchTimes);
    }
}
