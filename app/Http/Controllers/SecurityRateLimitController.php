<?php

namespace App\Http\Controllers;


use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
use App\Http\Resources\Tag\TagResource;
use App\Models\SecurityRateLimit;
use App\Models\Tag;
use App\Models\TokenPoint;
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
        $data = SecurityRateLimit::raw(function($collection){
            return $collection->aggregate([
                /*['$match' => [
                    //'date' => ['$gte'=> SecurityRateLimit::fromDateTime(Carbon::now()->subDays(3))],
                    //'video_id' => ['$in'=> $podcastIds],
                ]],*/
                ['$group' => [
                    '_id' => '$user_id',
                    'count' => [
                        '$sum' => 1
                    ],
                ]],
                ['$sort' => ['count' => -1]],
                /*['$limit' => 24]*/
            ]);
        });


        $userIds = SecurityRateLimit::raw(function($collection){
            return $collection->aggregate([
                ['$group' => [
                    '_id' => '$user_id',
                    'count' => [
                        '$sum' => 1
                    ],
                ]],
                ['$match' => [
                    'count' => ['$lte'=> 100],
                ]],
                ['$sort' => ['count' => -1]],
            ]);
        })->pluck('_id')->toArray();

        $amount = TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->where('activate_at', '>=', Carbon::now()->subDay()->startOfDay())->sum('amount');


        return response()->json(['group_by_user' => $data, 'amount_of_users_below_100_requests' => $amount]);
    }

    public function restoreBlockedTokens()
    {
        $userId = 52263;

        TokenPoint::where('user_id', $userId)->whereNotNull('claimable_by')->update(['claimable_at' => null, 'claimable_by' => null]);

        Cache::forget("\App\Http\Controllers\VideoController@watch_time_store.ip212.68.59.152.block");
    }
}
