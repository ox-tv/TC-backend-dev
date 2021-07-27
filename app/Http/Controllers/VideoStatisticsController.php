<?php

namespace App\Http\Controllers;

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
        $video_query = Video::where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        });

        if (!$request->is('api/admin/*')){
            $video_query->mine();
        }

        $video = $video_query->firstOrFail();


        $statistics_query = VideoStatisticsDaily::where([
            'video_id' => $video->id
        ]);

        $filters = $request->get('filters', []);

        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        if($fromFilter){
            $statistics_query->where('date', '>=', $fromFilter);
        }

        if($toFilter){
            $statistics_query->where('date', '<=', $toFilter);
        }

        return $statistics_query->get();
    }
}
