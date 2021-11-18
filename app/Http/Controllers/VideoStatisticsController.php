<?php

namespace App\Http\Controllers;

use App\Http\Resources\VideoStatisticsDaily\VideoStatisticsDailyItem;
use App\Models\Option;
use App\Models\Playlist;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class VideoStatisticsController extends Controller
{
    public function index(Request $request, $idOrHash)
    {
        // Check Video is mine or route is admin
        $videoQuery = Video::query();

        if (!$request->is('api/admin/*')){
            $videoQuery->mine();
        }

        $videoQuery->where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        });

        $video = $videoQuery->firstOrFail();


        $statisticsQuery = VideoStatisticsDaily::where([
            'video_id' => $video->id
        ]);

        $filters = $request->get('filters', []);

        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        if($fromFilter){
            $statisticsQuery->where('date', '>=', $fromFilter);
        }

        if($toFilter){
            $statisticsQuery->where('date', '<=', $toFilter);
        }

        return VideoStatisticsDailyItem::collection($statisticsQuery->get());
    }

    public function videoStatisticsOverview(Request $request, $idOrHash)
    {
        // Check Video is mine or route is admin
        $videoQuery = Video::query();

        if (!$request->is('api/admin/*')){
            $videoQuery->mine();
        }

        $videoQuery->where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        });

        $video = $videoQuery->firstOrFail();


        $statisticsQuery = VideoStatisticsDaily::where([
            'video_id' => $video->id
        ]);

        $filters = $request->get('filters', []);

        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        if($fromFilter){
            $statisticsQuery->where('date', '>=', $fromFilter);
        }

        if($toFilter){
            $statisticsQuery->where('date', '<=', $toFilter);
        }

        return response()->json([
            'points' => $statisticsQuery->sum('points'),
            'views_hero' => $statisticsQuery->sum('views_hero'),
            'views_non_hero' => $statisticsQuery->sum('views_non_hero'),
            'views_total' => $statisticsQuery->sum('views_total'),
            'likes_hero' => $statisticsQuery->sum('likes_hero'),
            'likes_non_hero' => $statisticsQuery->sum('likes_non_hero'),
            'likes_total' => $statisticsQuery->sum('likes_total'),
            'dislikes_hero' => $statisticsQuery->sum('dislikes_hero'),
            'dislikes_non_hero' => $statisticsQuery->sum('dislikes_non_hero'),
            'dislikes_total' => $statisticsQuery->sum('dislikes_total'),
            'comments_hero' => $statisticsQuery->sum('comments_hero'),
            'comments_non_hero' => $statisticsQuery->sum('comments_non_hero'),
            'comments_total' => $statisticsQuery->sum('comments_total'),
            'watch_time_hero' => $statisticsQuery->sum('watch_time_hero'),
            'watch_time_non_hero' => $statisticsQuery->sum('watch_time_non_hero'),
            'watch_time_total' => $statisticsQuery->sum('watch_time_total'),
        ]);
    }
}
