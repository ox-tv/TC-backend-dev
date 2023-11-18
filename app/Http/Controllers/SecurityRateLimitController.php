<?php

namespace App\Http\Controllers;


use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
use App\Http\Resources\Tag\TagResource;
use App\Models\SecurityRateLimit;
use App\Models\Tag;
use App\Repository\Eloquent\TagRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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

        return response()->json($data);
    }

}
