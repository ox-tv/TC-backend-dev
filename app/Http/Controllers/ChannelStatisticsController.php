<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChannelStatisticsDaily\ChannelStatisticsDailyItem;
use App\Http\Resources\VideoStatisticsDaily\VideoStatisticsDailyItem;
use App\Models\Channel;
use App\Models\ChannelStatisticsDaily;
use App\Models\Option;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use App\Services\PointService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

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
            'points' => floatval($videoStatistics->sum('points')),
            'views_hero' => floatval($videoStatistics->sum('views_hero')),
            'views_non_hero' => floatval($videoStatistics->sum('views_non_hero')),
            'views_total' => floatval($videoStatistics->sum('views_total')),
            'likes_hero' => ($temp = $videoStatistics->sum('likes_hero')) > 0? floatval($temp) : 0,
            'likes_non_hero' => ($temp = $videoStatistics->sum('likes_non_hero')) > 0? floatval($temp) : 0,
            'likes_total' => ($temp = $videoStatistics->sum('likes_total')) > 0? floatval($temp) : 0,
            'dislikes_hero' => ($temp = $videoStatistics->sum('dislikes_hero')) > 0? floatval($temp) : 0,
            'dislikes_non_hero' => ($temp = $videoStatistics->sum('dislikes_non_hero')) > 0? floatval($temp) : 0,
            'dislikes_total' => ($temp = $videoStatistics->sum('dislikes_total')) > 0? floatval($temp) : 0,
            'comments_hero' => floatval($videoStatistics->sum('comments_hero')),
            'comments_non_hero' => floatval($videoStatistics->sum('comments_non_hero')),
            'comments_total' => floatval($videoStatistics->sum('comments_total')),
            'watch_time_hero' => floatval($videoStatistics->sum('watch_time_hero')),
            'watch_time_non_hero' => floatval($videoStatistics->sum('watch_time_non_hero')),
            'watch_time_total' => floatval($videoStatistics->sum('watch_time_total')),
            'subscribers_hero' => ($temp = $channelStatistics->sum('subscribers_hero')) > 0? floatval($temp) : 0,
            'subscribers_non_hero' => ($temp = $channelStatistics->sum('subscribers_non_hero')) > 0? floatval($temp) : 0,
            'subscribers_total' => ($temp = $channelStatistics->sum('subscribers_total')) > 0? floatval($temp) : 0,
            'unsubscribers_hero' => ($temp = $channelStatistics->sum('unsubscribers_hero')) > 0? floatval($temp) : 0,
            'unsubscribers_non_hero' => ($temp = $channelStatistics->sum('unsubscribers_non_hero')) > 0? floatval($temp) : 0,
            'unsubscribers_total' => ($temp = $channelStatistics->sum('unsubscribers_total')) > 0? floatval($temp) : 0,
            'upload_videos_total' => floatval($channelStatistics->sum('upload_videos_total')),
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
                ];
            }
        }

        return $result;
    }
}
