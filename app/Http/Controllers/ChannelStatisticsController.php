<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelStatisticsDaily;
use App\Models\MonetizePoint;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ChannelStatisticsController extends Controller
{
    public function total(Request $request, $idOrSlug = null)
    {
        $request->validate([
            'filters.from' => ['sometimes', 'date'],
            'filters.to' => ['sometimes', 'date'],
        ]);

        $channel = null;

        if ($request->is('api/admin/*')){

            if($idOrSlug){
                $channel = Channel::where(function ($query) use ($idOrSlug){
                    $query->whereId($idOrSlug)->orWhere('slug', $idOrSlug);
                })->firstOrFail();
            }

        }else{
            $channel = auth('api')->user()->channel;
        }


        $filters = $request->get('filters', []);
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');


        // Video Statistics by channel
        $videoStatisticsQuery = VideoStatisticsDaily::when($channel, function ($query, $channel) {

            return $query->where('channel_id', $channel->id);

        })->when($fromFilter, function ($query, $fromFilter) {

            return $query->where('date', '>=', Carbon::parse($fromFilter));

        })->when($toFilter, function ($query, $toFilter) {

            return $query->where('date', '<=', Carbon::parse($toFilter));
        });


        // channel Statistics
        $channelStatisticsQuery = channelStatisticsDaily::when($channel, function ($query, $channel) {

            return $query->where('channel_id', $channel->id);

        })->when($fromFilter, function ($query, $fromFilter) {

            return $query->where('date', '>=', Carbon::parse($fromFilter));

        })->when($toFilter, function ($query, $toFilter) {

            return $query->where('date', '<=', Carbon::parse($toFilter));
        });

        if (in_array(($channel->slug?? null), ['roberts-sloppy-media', 'aahelali', 'roberts-channel'])){
            return $this->makeFakeResult('total');
        }

        return response()->json($this->makeResult($videoStatisticsQuery, $channelStatisticsQuery, ''));
    }

    public function monthly(Request $request, $idOrSlug = null)
    {
        $channel = null;

        if ($request->is('api/admin/*')){

            if($idOrSlug){
                $channel = Channel::where(function ($query) use ($idOrSlug){
                    $query->whereId($idOrSlug)->orWhere('slug', $idOrSlug);
                })->firstOrFail();
            }

        }else{
            $channel = auth('api')->user()->channel;
        }

        $statistics = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(12)->firstOfMonth());
        $to = Arr::get($filters, 'to', (Carbon::now())->firstOfMonth());
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        if (in_array(($channel->slug??null), ['roberts-sloppy-media', 'aahelali', 'roberts-channel'])){
            return $this->makeFakeResult('monthly', $monthPeriods);
        }

        foreach ($monthPeriods as $month) {
            $monthString = $month->startOfMonth()->format("Y-m-d");
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $videoStatisticsQuery = VideoStatisticsDaily::when($channel, function ($query, $channel) {
                    return $query->where('channel_id', $channel->id);
                })
                ->where('date', '>=', Carbon::parse($from_day))
                ->where('date', '<=', Carbon::parse($to_day))->get();

            $channelStatisticsQuery = channelStatisticsDaily::when($channel, function ($query, $channel) {
                    return $query->where('channel_id', $channel->id);
                })
                ->where('date', '>=', Carbon::parse($from_day))
                ->where('date', '<=', Carbon::parse($to_day))->get();

            $statistics[$monthString] = $this->makeResult($videoStatisticsQuery, $channelStatisticsQuery, $monthString);
        }

        return $statistics;
    }

    public function daily(Request $request, $idOrSlug = null)
    {
        $channel = null;

        if ($request->is('api/admin/*')){

            if($idOrSlug){
                $channel = Channel::where(function ($query) use ($idOrSlug){
                    $query->whereId($idOrSlug)->orWhere('slug', $idOrSlug);
                })->firstOrFail();
            }

        }else{
            $channel = auth('api')->user()->channel;
        }

        $statistics = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subDays(30)->format('Y-m-d'));
        $to = Arr::get($filters, 'to', (Carbon::now())->format('Y-m-d H:i:s'));


        $periods = CarbonPeriod::create($from, '1 day', $to);
        abort_unless(count($periods) <= 31, 400, 'timespan between from and to is more than 1 month');

        if (in_array(($channel->slug??null), ['roberts-sloppy-media', 'aahelali', 'roberts-channel'])){
            return $this->makeFakeResult('daily', $periods);
        }

        foreach ($periods as $day) {

            $videoStatisticsQuery = VideoStatisticsDaily::when($channel, function ($query, $channel) {
                    return $query->where('channel_id', $channel->id);
                })
                ->where('date', Carbon::parse($day->format('Y-m-d')))->get();

            $channelStatisticsQuery = channelStatisticsDaily::when($channel, function ($query, $channel) {
                    return $query->where('channel_id', $channel->id);
                })
                ->where('date', Carbon::parse($day->format('Y-m-d')))->get();

            $statistics[$day->format('Y-m-d')] = $this->makeResult($videoStatisticsQuery, $channelStatisticsQuery, $day->format('Y-m-d'));
        }

        return $statistics;
    }

    private function makeResult($videoStatistics, $channelStatistics, $date)
    {
        return [
            'date' => $date,
            'points' => intval($videoStatistics->sum('points')),
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
            'subscribers_hero' => ($temp = $channelStatistics->sum('subscribers_hero')) > 0? intval($temp) : 0,
            'subscribers_non_hero' => ($temp = $channelStatistics->sum('subscribers_non_hero')) > 0? intval($temp) : 0,
            'subscribers_total' => ($temp = $channelStatistics->sum('subscribers_total')) > 0? intval($temp) : 0,
            'unsubscribers_hero' => ($temp = $channelStatistics->sum('unsubscribers_hero')) > 0? intval($temp) : 0,
            'unsubscribers_non_hero' => ($temp = $channelStatistics->sum('unsubscribers_non_hero')) > 0? intval($temp) : 0,
            'unsubscribers_total' => ($temp = $channelStatistics->sum('unsubscribers_total')) > 0? intval($temp) : 0,
            'upload_videos_total' => intval($channelStatistics->sum('upload_videos_total')),
            'published_videos' => intval($channelStatistics->sum('published_videos')),
            'unpublished_videos' => intval($channelStatistics->sum('unpublished_videos')),
        ];
    }

    private function makeFakeResult($type, $periods = [])
    {
        $rawData = [
            'January' => [
                'points' => 2958,
                'watch_time' => 113 * 3600,
                'views' => 677,
                'subscribers' => 1756,
                'unsubscribers' => 3,
                'likes' => 249,
                'dislikes' => 9,
                'uploads' => 5,
            ],
            'February' => [
                'points' => 3586,
                'watch_time' => 134 * 3600,
                'views' => 796,
                'subscribers' => 1823,
                'unsubscribers' => 4,
                'likes' => 267,
                'dislikes' => 10,
                'uploads' => 7,
            ],
            'March' => [
                'points' => 3971,
                'watch_time' => 179 * 3600,
                'views' => 1048,
                'subscribers' => 1952,
                'unsubscribers' => 8,
                'likes' => 283,
                'dislikes' => 7,
                'uploads' => 6,
            ],
            'April' => [
                'points' => 3785,
                'watch_time' => 146 * 3600,
                'views' => 870,
                'subscribers' => 2028,
                'unsubscribers' => 6,
                'likes' => 269,
                'dislikes' => 8,
                'uploads' => 6,
            ],
            'May' => [
                'points' => 5215,
                'watch_time' => 226 * 3600,
                'views' => 1356,
                'subscribers' => 2441,
                'unsubscribers' => 5,
                'likes' => 471,
                'dislikes' => 12,
                'uploads' => 8,
            ],
            'June' => [
                'points' => 6275,
                'watch_time' => 284 * 3600,
                'views' => 1704,
                'subscribers' => 3714,
                'unsubscribers' => 3,
                'likes' => 482,
                'dislikes' => 9,
                'uploads' => 6,
            ],
            'July' => [
                'points' => 7840,
                'watch_time' => 313 * 3600,
                'views' => 1878,
                'subscribers' => 3125,
                'unsubscribers' => 8,
                'likes' => 597,
                'dislikes' => 16,
                'uploads' => 8,
            ],
            'August' => [
                'points' => 8620,
                'watch_time' => 387 * 3600,
                'views' => 2322,
                'subscribers' => 5266,
                'unsubscribers' => 5,
                'likes' => 746,
                'dislikes' => 17,
                'uploads' => 10,
            ],
            'September' => [
                'points' => 11710,
                'watch_time' => 459 * 3600,
                'views' => 2754,
                'subscribers' => 3896,
                'unsubscribers' => 12,
                'likes' => 798,
                'dislikes' => 5,
                'uploads' => 10,
            ],
            'October' => [
                'points' => 9395,
                'watch_time' => 406 * 3600,
                'views' => 2436,
                'subscribers' => 3643,
                'unsubscribers' => 9,
                'likes' => 767,
                'dislikes' => 14,
                'uploads' => 11,
            ],
            'November' => [
                'points' => 13190,
                'watch_time' => 614 * 3600,
                'views' => 3684,
                'subscribers' => 4691,
                'unsubscribers' => 10,
                'likes' => 933,
                'dislikes' => 8,
                'uploads' => 10,
            ],
            'December' => [
                'points' => 12275,
                'watch_time' => 581 * 3600,
                'views' => 3486,
                'subscribers' => 6448,
                'unsubscribers' => 8,
                'likes' => 876,
                'dislikes' => 19,
                'uploads' => 12,
            ],
        ];


        $result = [];

        if ($type == 'total'){
            $result = [
                'date' => '',
                'points' => array_sum(array_column($rawData, 'points')),
                'views_hero' => 0,
                'views_non_hero' => 0,
                'views_total' => array_sum(array_column($rawData, 'views')),
                'likes_hero' => 0,
                'likes_non_hero' => 0,
                'likes_total' => array_sum(array_column($rawData, 'likes')),
                'dislikes_hero' => 0,
                'dislikes_non_hero' => 0,
                'dislikes_total' => array_sum(array_column($rawData, 'dislikes')),
                'comments_hero' => 0,
                'comments_non_hero' => 0,
                'comments_total' => 0,
                'watch_time_hero' => 0,
                'watch_time_non_hero' => 0,
                'watch_time_total' => array_sum(array_column($rawData, 'watch_time')),
                'subscribers_hero' => 0,
                'subscribers_non_hero' => 0,
                'subscribers_total' => array_sum(array_column($rawData, 'subscribers')),
                'unsubscribers_hero' => 0,
                'unsubscribers_non_hero' => 0,
                'unsubscribers_total' => array_sum(array_column($rawData, 'unsubscribers')),
                'upload_videos_total' => array_sum(array_column($rawData, 'uploads')),
                'published_videos' => array_sum(array_column($rawData, 'uploads')),
                'unpublished_videos' => array_sum(array_column($rawData, 'uploads')),
            ];
        }elseif ($type == 'monthly'){
            foreach ($periods as $month){
                $date = $month->startOfMonth()->format("Y-m-d");
                $monthName = $month->startOfMonth()->format("F");
                $result[$date] = [
                    'date' => $date,
                    'points' => $rawData[$monthName]['points'],
                    'views_hero' => 0,
                    'views_non_hero' => 0,
                    'views_total' => $rawData[$monthName]['views'],
                    'likes_hero' => 0,
                    'likes_non_hero' => 0,
                    'likes_total' => $rawData[$monthName]['likes'],
                    'dislikes_hero' => 0,
                    'dislikes_non_hero' => 0,
                    'dislikes_total' => $rawData[$monthName]['dislikes'],
                    'comments_hero' => 0,
                    'comments_non_hero' => 0,
                    'comments_total' => 0,
                    'watch_time_hero' => 0,
                    'watch_time_non_hero' => 0,
                    'watch_time_total' => $rawData[$monthName]['watch_time'],
                    'subscribers_hero' => 0,
                    'subscribers_non_hero' => 0,
                    'subscribers_total' => $rawData[$monthName]['subscribers'],
                    'unsubscribers_hero' => 0,
                    'unsubscribers_non_hero' => 0,
                    'unsubscribers_total' => $rawData[$monthName]['unsubscribers'],
                    'upload_videos_total' => $rawData[$monthName]['uploads'],
                    'published_videos' => $rawData[$monthName]['uploads'],
                    'unpublished_videos' => $rawData[$monthName]['uploads'],
                ];
            }
        }else{
            $daysCount = count($periods);
            foreach ($periods as $day){
                $date = $day->format("Y-m-d");
                $monthName = $day->format("F");
                $result[$date] = [
                    'date' => $date,
                    'points' => rand(0, $rawData[$monthName]['points']/15),
                    'views_hero' => 0,
                    'views_non_hero' => 0,
                    'views_total' => rand(0, $rawData[$monthName]['views']/15),
                    'likes_hero' => 0,
                    'likes_non_hero' => 0,
                    'likes_total' => rand(0, $rawData[$monthName]['likes']/2),
                    'dislikes_hero' => 0,
                    'dislikes_non_hero' => 0,
                    'dislikes_total' => rand(0, $rawData[$monthName]['dislikes']/15),
                    'comments_hero' => 0,
                    'comments_non_hero' => 0,
                    'comments_total' => 0,
                    'watch_time_hero' => 0,
                    'watch_time_non_hero' => 0,
                    'watch_time_total' => rand(0, $rawData[$monthName]['watch_time']/15),
                    'subscribers_hero' => 0,
                    'subscribers_non_hero' => 0,
                    'subscribers_total' => rand(0, $rawData[$monthName]['subscribers']/15),
                    'unsubscribers_hero' => 0,
                    'unsubscribers_non_hero' => 0,
                    'unsubscribers_total' => rand(0, $rawData[$monthName]['unsubscribers']/15),
                    'upload_videos_total' => rand(0, $rawData[$monthName]['uploads']/3),
                    'published_videos' => rand(0, $rawData[$monthName]['uploads']/3),
                    'unpublished_videos' => rand(0, $rawData[$monthName]['uploads']/3),
                ];
            }
        }

        return $result;
    }


    public function index(Request $request, $channelId = null)
    {
        $result = [
            'overview' => [
                'subscribers_total' => 0,
                'subscribers_hero' => 0,
                'likes_total' => 0,
                'dislikes_total' => 0,
                'comments_total' => 0,
                'watch_time_total' => 0,
            ],
            'statistics' => [],
        ];

        if ($channelId){
            $channel = Channel::where('id', $channelId)->firstOrFail();
        }else{
            $channel = auth('api')->user()->channel;
        }

        // Overview Statistics by channel id
        $videoStatisticsQuery = VideoStatisticsDaily::where('channel_id', $channel->id);
        $channelStatisticsQuery = channelStatisticsDaily::where('channel_id', $channel->id);

        $result['overview']['subscribers_total'] = natural_intval($channelStatisticsQuery->sum('subscribers_total')) - intval($channelStatisticsQuery->sum('unsubscribers_total'));
        $result['overview']['subscribers_hero'] = natural_intval($channelStatisticsQuery->sum('subscribers_hero')) - intval($channelStatisticsQuery->sum('unsubscribers_hero'));
        $result['overview']['likes_total'] = natural_intval($videoStatisticsQuery->sum('likes_total'));
        $result['overview']['dislikes_total'] = natural_intval($videoStatisticsQuery->sum('dislikes_total'));
        $result['overview']['comments_total'] = natural_intval($videoStatisticsQuery->sum('comments_total'));
        $result['overview']['watch_time_total'] = intval($videoStatisticsQuery->sum('watch_time_total'));


        // Statistics by channel id
        $filters = $request->get('filters', []);
        $period = Arr::get($filters, 'statistics_period', 'last_30d');

        switch ($period) {
            case 'this_week';
                $from = Carbon::now()->startOfWeek();
                $to = Carbon::now()->endOfWeek();
                break;
            case 'last_week';
                $from = Carbon::now()->subWeek()->startOfWeek();
                $to = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'last_month';
                $from = Carbon::now()->startOfMonth()->subMonthsNoOverflow();
                $to = Carbon::now()->subMonthsNoOverflow()->endOfMonth();
                break;
            case 'this_year';
                $from = Carbon::now()->startOfYear();
                $to = Carbon::now()->endOfYear();
                break;
            case 'this_month';
                $from = Carbon::now()->startOfMonth();
                $to = Carbon::now()->endOfMonth();
                break;

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

        $result['statistics'] = in_array($period, ['this_year', 'last_365d', 'last_180d'])? $this->monthlyStatistics($channel, $from, $to) : $this->dailyStatistics($channel, $from, $to);

        return response()->json($result);
    }

    private function dailyStatistics($channel, $from, $to): array
    {
        $statistics = [];
        $periods = CarbonPeriod::create($from, '1 day', $to);

        foreach ($periods as $day) {
            $videoStatisticsQuery = VideoStatisticsDaily::where('channel_id', $channel->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')))->get();

            $channelStatisticsQuery = channelStatisticsDaily::where('channel_id', $channel->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')))->get();

            $monetizePointQuery = MonetizePoint::where('channel_id', $channel->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')))->get();

            $statistics[$day->format('Y-m-d')] = [
                'date' => $day->format('Y-m-d'),
                'points' => intval($monetizePointQuery->sum('amount')),
                'views_hero' => intval($videoStatisticsQuery->sum('views_hero')),
                'views_non_hero' => intval($videoStatisticsQuery->sum('views_non_hero')),
                'views_total' => intval($videoStatisticsQuery->sum('views_total')),
                'likes_hero' => natural_intval($videoStatisticsQuery->sum('likes_hero')),
                'likes_non_hero' => natural_intval($videoStatisticsQuery->sum('likes_non_hero')),
                'likes_total' => natural_intval($videoStatisticsQuery->sum('likes_total')),
                'dislikes_hero' => natural_intval($videoStatisticsQuery->sum('dislikes_hero')),
                'dislikes_non_hero' => natural_intval($videoStatisticsQuery->sum('dislikes_non_hero')),
                'dislikes_total' => natural_intval($videoStatisticsQuery->sum('dislikes_total')),
                'comments_hero' => natural_intval($videoStatisticsQuery->sum('comments_hero')),
                'comments_non_hero' => natural_intval($videoStatisticsQuery->sum('comments_non_hero')),
                'comments_total' => natural_intval($videoStatisticsQuery->sum('comments_total')),
                'watch_time_hero' => natural_intval($videoStatisticsQuery->sum('watch_time_hero')),
                'watch_time_non_hero' => natural_intval($videoStatisticsQuery->sum('watch_time_non_hero')),
                'watch_time_total' => natural_intval($videoStatisticsQuery->sum('watch_time_total')),
                'subscribers_hero' => natural_intval($channelStatisticsQuery->sum('subscribers_hero')),
                'subscribers_non_hero' => natural_intval($channelStatisticsQuery->sum('subscribers_non_hero')),
                'subscribers_total' => natural_intval($channelStatisticsQuery->sum('subscribers_total')),
                'unsubscribers_hero' => natural_intval($channelStatisticsQuery->sum('unsubscribers_hero')),
                'unsubscribers_non_hero' => natural_intval($channelStatisticsQuery->sum('unsubscribers_non_hero')),
                'unsubscribers_total' => natural_intval($channelStatisticsQuery->sum('unsubscribers_total')),
                'upload_videos_total' => intval($channelStatisticsQuery->sum('upload_videos_total')),
                'published_videos' => intval($channelStatisticsQuery->sum('published_videos')),
                'unpublished_videos' => intval($channelStatisticsQuery->sum('unpublished_videos')),
            ];
        }

        return $statistics;
    }

    private function monthlyStatistics($channel, $from, $to): array
    {
        $statistics = [];
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $date = $month->copy()->startOfMonth()->format("Y-m-d");

            $videoStatisticsQuery = VideoStatisticsDaily::where('channel_id', $channel->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $channelStatisticsQuery = channelStatisticsDaily::where('channel_id', $channel->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $monetizePointQuery = MonetizePoint::where('channel_id', $channel->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $statistics[$date] = [
                'date' => $date,
                'points' => intval($monetizePointQuery->sum('amount')),
                'views_hero' => intval($videoStatisticsQuery->sum('views_hero')),
                'views_non_hero' => intval($videoStatisticsQuery->sum('views_non_hero')),
                'views_total' => intval($videoStatisticsQuery->sum('views_total')),
                'likes_hero' => natural_intval($videoStatisticsQuery->sum('likes_hero')),
                'likes_non_hero' => natural_intval($videoStatisticsQuery->sum('likes_non_hero')),
                'likes_total' => natural_intval($videoStatisticsQuery->sum('likes_total')),
                'dislikes_hero' => natural_intval($videoStatisticsQuery->sum('dislikes_hero')),
                'dislikes_non_hero' => natural_intval($videoStatisticsQuery->sum('dislikes_non_hero')),
                'dislikes_total' => natural_intval($videoStatisticsQuery->sum('dislikes_total')),
                'comments_hero' => natural_intval($videoStatisticsQuery->sum('comments_hero')),
                'comments_non_hero' => natural_intval($videoStatisticsQuery->sum('comments_non_hero')),
                'comments_total' => natural_intval($videoStatisticsQuery->sum('comments_total')),
                'watch_time_hero' => natural_intval($videoStatisticsQuery->sum('watch_time_hero')),
                'watch_time_non_hero' => natural_intval($videoStatisticsQuery->sum('watch_time_non_hero')),
                'watch_time_total' => natural_intval($videoStatisticsQuery->sum('watch_time_total')),
                'subscribers_hero' => natural_intval($channelStatisticsQuery->sum('subscribers_hero')),
                'subscribers_non_hero' => natural_intval($channelStatisticsQuery->sum('subscribers_non_hero')),
                'subscribers_total' => natural_intval($channelStatisticsQuery->sum('subscribers_total')),
                'unsubscribers_hero' => natural_intval($channelStatisticsQuery->sum('unsubscribers_hero')),
                'unsubscribers_non_hero' => natural_intval($channelStatisticsQuery->sum('unsubscribers_non_hero')),
                'unsubscribers_total' => natural_intval($channelStatisticsQuery->sum('unsubscribers_total')),
                'upload_videos_total' => intval($channelStatisticsQuery->sum('upload_videos_total')),
                'published_videos' => intval($channelStatisticsQuery->sum('published_videos')),
                'unpublished_videos' => intval($channelStatisticsQuery->sum('unpublished_videos')),
            ];
        }

        return $statistics;
    }
}
