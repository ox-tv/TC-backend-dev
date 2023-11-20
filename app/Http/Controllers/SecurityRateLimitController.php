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
        $userIds = [74658,
            74657,
            74656,
            74653,
            74652,
            74651,
            74650,
            74649,
            74648,
            73770,
            73765,
            73764,
            73763,
            73759,
            73758,
            73757,
            73756,
            73755,
            73754,
            73552,
            73551,
            73550,
            73549,
            73548,
            73547,
            73546,
            73545,
            73544,
            73543,
            73425,
            73424,
            73423,
            73422,
            73417,
            73416,
            73415,
            73414,
            73413,
            73412,
            73346,
            73344,
            73343,
            73342,
            73324,
            73323,
            73322,
            73301,
            73300,
            73299];
        $carbonNow = Carbon::now();

        TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime($carbonNow), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds)->update(['status' => User::STATUS_INACTIVE]);

        return response()->json(['tokens' => TokenPoint::whereIn('user_id', $userIds)->whereNotNull('claimable_by')->get()]);
    }
}
