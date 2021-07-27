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
}
