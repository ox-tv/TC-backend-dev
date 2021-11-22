<?php

namespace App\Http\Controllers;

use App\Http\Resources\VideoStatisticsDaily\VideoStatisticsDailyItem;
use App\Models\Channel;
use App\Models\ChannelStatisticsDaily;
use App\Models\Option;
use App\Models\Playlist;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class VideoStatisticsController extends Controller
{
    public function daily(Request $request, $idOrHash)
    {
        // Check Video is mine or route is admin
        $videoQuery = Video::where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        });

        if (!$request->is('api/admin/*')){
            $videoQuery->mine();
        }

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

        foreach ($statisticsQuery->get() as $row){
            $statistics[$row->date] = VideoStatisticsDailyItem::make($row);
        }

        return $statistics;
    }

    public function monthly(Request $request, $idOrHash)
    {
        // Check Video is mine or route is admin
        $videoQuery = Video::where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        });

        if (!$request->is('api/admin/*')){
            $videoQuery->mine();
        }

        $video = $videoQuery->firstOrFail();

        $statistics = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(12)->firstOfMonth());
        $to = Arr::get($filters, 'to', (Carbon::now())->firstOfMonth());
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);


        foreach ($monthPeriods as $month) {
            $monthString = $month->startOfMonth()->format("Y-m-d");
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $videoStatisticsQuery = VideoStatisticsDaily::where([
                    'video_id' => $video->id
                ])
                ->whereDate('date', '>=', $from_day)
                ->whereDate('date', '<=', $to_day)->get();

            $statistics[$monthString] = $this->makeResult($videoStatisticsQuery, $monthString);
        }

        return $statistics;
    }

    public function total(Request $request, $idOrHash)
    {
        // Check Video is mine or route is admin
        $videoQuery = Video::where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        });

        if (!$request->is('api/admin/*')){
            $videoQuery->mine();
        }

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

        return response()->json($this->makeResult($statisticsQuery, ''));
    }

    private function makeResult($videoStatistics, $date)
    {
        return [
            'date' => $date,
            'points' => $videoStatistics->sum('points'),
            'views_hero' => $videoStatistics->sum('views_hero'),
            'views_non_hero' => $videoStatistics->sum('views_non_hero'),
            'views_total' => $videoStatistics->sum('views_total'),
            'likes_hero' => ($temp = $videoStatistics->sum('likes_hero')) > 0? $temp : 0,
            'likes_non_hero' => ($temp = $videoStatistics->sum('likes_non_hero')) > 0? $temp : 0,
            'likes_total' => ($temp = $videoStatistics->sum('likes_total')) > 0? $temp : 0,
            'dislikes_hero' => ($temp = $videoStatistics->sum('dislikes_hero')) > 0? $temp : 0,
            'dislikes_non_hero' => ($temp = $videoStatistics->sum('dislikes_non_hero')) > 0? $temp : 0,
            'dislikes_total' => ($temp = $videoStatistics->sum('dislikes_total')) > 0? $temp : 0,
            'comments_hero' => $videoStatistics->sum('comments_hero'),
            'comments_non_hero' => $videoStatistics->sum('comments_non_hero'),
            'comments_total' => $videoStatistics->sum('comments_total'),
            'watch_time_hero' => $videoStatistics->sum('watch_time_hero'),
            'watch_time_non_hero' => $videoStatistics->sum('watch_time_non_hero'),
            'watch_time_total' => $videoStatistics->sum('watch_time_total'),
        ];
    }
}
