<?php

namespace App\Http\Controllers;

use App\Http\Resources\VideoStatisticsDaily\VideoStatisticsDailyItem;
use App\Models\Channel2StatisticsDaily;
use App\Models\MonetizePoint;
use App\Models\Video;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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


        $statisticsQuery = Channel2StatisticsDaily::where([
            'video_id' => $video->id
        ]);

        $filters = $request->get('filters', []);

        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        if($fromFilter){
            $statisticsQuery->where('date', '>=', Carbon::parse($fromFilter));
        }

        if($toFilter){
            $statisticsQuery->where('date', '<=', Carbon::parse($toFilter));
        }

        $statistics = [];

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

            $videoStatisticsQuery = Channel2StatisticsDaily::where([
                    'video_id' => $video->id
                ])
                ->where('date', '>=', Carbon::parse($from_day))
                ->where('date', '<=', Carbon::parse($to_day))->get();

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


        $statisticsQuery = Channel2StatisticsDaily::where([
            'video_id' => $video->id
        ]);

        $filters = $request->get('filters', []);

        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        if($fromFilter){
            $statisticsQuery->where('date', '>=', Carbon::parse($fromFilter));
        }

        if($toFilter){
            $statisticsQuery->where('date', '<=', Carbon::parse($toFilter));
        }

        return response()->json($this->makeResult($statisticsQuery, ''));
    }

    private function makeResult($videoStatistics, $date)
    {
        return [
            'date' => $date,
            'points' => 0, // TODO: Remove this row after a while
            'views_hero' => intval($videoStatistics->sum('views_hero')),
            'views_non_hero' => intval($videoStatistics->sum('views_non_hero')),
            'views_total' => intval($videoStatistics->sum('views_total')),
            'likes_hero' => ($temp = $videoStatistics->sum('likes_hero')) > 0? intval($temp) : 0,
            'likes_non_hero' => ($temp = $videoStatistics->sum('likes_non_hero')) > 0? intval($temp) : 0,
            'likes_total' => ($temp = $videoStatistics->sum('likes_total')) > 0? intval($temp) : 0,
            'dislikes_hero' => ($temp = $videoStatistics->sum('dislikes_hero')) > 0? intval($temp) : 0,
            'dislikes_non_hero' => ($temp = $videoStatistics->sum('dislikes_non_hero')) > 0? intval($temp) : 0,
            'dislikes_total' => ($temp = $videoStatistics->sum('dislikes_total')) > 0? intval($temp) : 0,
            'comments_hero' => intval($videoStatistics->sum('comments_hero')),
            'comments_non_hero' => intval($videoStatistics->sum('comments_non_hero')),
            'comments_total' => intval($videoStatistics->sum('comments_total')),
            'watch_time_hero' => intval($videoStatistics->sum('watch_time_hero')),
            'watch_time_non_hero' => intval($videoStatistics->sum('watch_time_non_hero')),
            'watch_time_total' => intval($videoStatistics->sum('watch_time_total')),
        ];
    }

    public function index(Request $request, $videoIdOrHash)
    {
        $result = [
            'overview' => [
                'points' => 0,
                'watch_time_total' => 0,
                'views_total' => 0,
                'likes_total' => 0,
                'dislikes_total' => 0,
            ],
            'statistics' => [],
        ];

        // Validate video
        $videoQuery = Video::where(function ($query) use ($videoIdOrHash){
            $query->whereId($videoIdOrHash)->orWhere('url_hash', $videoIdOrHash);
        });

        if (!$request->is('api/admin/*')){
            $videoQuery->mine();
        }

        $video = $videoQuery->firstOrFail();

        // Get overview data
        $statisticsQuery = Channel2StatisticsDaily::where(['video_id' => $video->id]);
        $monetizePointQuery = MonetizePoint::where('related_to_type', Video::class)->where('related_to_id', $video->id);

        $result['overview']['points'] = natural_intval($monetizePointQuery->sum('amount'));
        $result['overview']['watch_time_total'] = natural_intval($statisticsQuery->sum('watch_time_total'));
        $result['overview']['views_total'] = natural_intval($statisticsQuery->sum('views_total'));
        $result['overview']['likes_total'] = natural_intval($statisticsQuery->sum('likes_total'));
        $result['overview']['dislikes_total'] = natural_intval($statisticsQuery->sum('dislikes_total'));


        // Statistics by channel id
        $filters = $request->get('filters', []);
        $period = Arr::get($filters, 'statistics_period', 'last_30d');

        switch ($period) {
            case 'last_7d';
                $from = Carbon::now()->subDays(7)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_14d';
                $from = Carbon::now()->subDays(14)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_90d';
                $from = Carbon::now()->subDays(90)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_180d';
                $from = Carbon::now()->subDays(180)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_365d';
                $from = Carbon::now()->subDays(365)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_30d';
            default;
                $from = Carbon::now()->subDays(30)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
        }

        $result['statistics'] = in_array($period, ['this_year', 'last_365d', 'last_180d'])? $this->monthlyStatistics($video, $from, $to) : $this->dailyStatistics($video, $from, $to);

        return $result;
    }

    private function dailyStatistics($video, $from, $to): array
    {
        $statistics = [];
        $periods = CarbonPeriod::create($from, '1 day', $to);

        foreach ($periods as $day) {
            $videoStatisticsQuery = Channel2StatisticsDaily::where('video_id', $video->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')));

            $monetizePointQuery = MonetizePoint::where('related_to_type', Video::class)->where('related_to_id', $video->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')));

            $statistics[$day->format('Y-m-d')] = [
                'date' => $day->format('Y-m-d'),
                'points' => natural_intval($monetizePointQuery->sum('amount')),
                'views_hero' => natural_intval($videoStatisticsQuery->sum('views_hero')),
                'views_non_hero' => natural_intval($videoStatisticsQuery->sum('views_non_hero')),
                'views_total' => natural_intval($videoStatisticsQuery->sum('views_total')),
                'likes_hero' => natural_intval($videoStatisticsQuery->sum('likes_hero')),
                'likes_non_hero' => natural_intval($videoStatisticsQuery->sum('likes_non_hero')),
                'likes_total' => natural_intval($videoStatisticsQuery->sum('likes_total')),
                'dislikes_hero' => natural_intval($videoStatisticsQuery->sum('dislikes_hero')),
                'dislikes_non_hero' => natural_intval($videoStatisticsQuery->sum('dislikes_non_hero')),
                'dislikes_total' => natural_intval($videoStatisticsQuery->sum('dislikes_total')),
                'watch_time_hero' => natural_intval($videoStatisticsQuery->sum('watch_time_hero')),
                'watch_time_non_hero' => natural_intval($videoStatisticsQuery->sum('watch_time_non_hero')),
                'watch_time_total' => natural_intval($videoStatisticsQuery->sum('watch_time_total')),
            ];
        }

        return $statistics;
    }

    private function monthlyStatistics($video, $from, $to): array
    {
        $statistics = [];
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $date = $month->copy()->startOfMonth()->format("Y-m-d");

            $videoStatisticsQuery = Channel2StatisticsDaily::where('video_id', $video->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth());

            $monetizePointQuery = MonetizePoint::where('related_to_type', Video::class)->where('related_to_id', $video->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth());

            $statistics[$date] = [
                'date' => $date,
                'points' => natural_intval($monetizePointQuery->sum('amount')),
                'views_hero' => natural_intval($videoStatisticsQuery->sum('views_hero')),
                'views_non_hero' => natural_intval($videoStatisticsQuery->sum('views_non_hero')),
                'views_total' => natural_intval($videoStatisticsQuery->sum('views_total')),
                'likes_hero' => natural_intval($videoStatisticsQuery->sum('likes_hero')),
                'likes_non_hero' => natural_intval($videoStatisticsQuery->sum('likes_non_hero')),
                'likes_total' => natural_intval($videoStatisticsQuery->sum('likes_total')),
                'dislikes_hero' => natural_intval($videoStatisticsQuery->sum('dislikes_hero')),
                'dislikes_non_hero' => natural_intval($videoStatisticsQuery->sum('dislikes_non_hero')),
                'dislikes_total' => natural_intval($videoStatisticsQuery->sum('dislikes_total')),
                'watch_time_hero' => natural_intval($videoStatisticsQuery->sum('watch_time_hero')),
                'watch_time_non_hero' => natural_intval($videoStatisticsQuery->sum('watch_time_non_hero')),
                'watch_time_total' => natural_intval($videoStatisticsQuery->sum('watch_time_total')),
            ];
        }

        return $statistics;
    }
}
