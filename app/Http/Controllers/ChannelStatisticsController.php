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

            return $query->where('date', '>=', $fromFilter);

        })->when($toFilter, function ($query, $toFilter) {

            return $query->where('date', '<=', $toFilter);
        });


        // channel Statistics
        $channelStatisticsQuery = channelStatisticsDaily::when($channel, function ($query, $channel) {

            return $query->where('channel_id', $channel->id);

        })->when($fromFilter, function ($query, $fromFilter) {

            return $query->where('date', '>=', $fromFilter);

        })->when($toFilter, function ($query, $toFilter) {

            return $query->where('date', '<=', $toFilter);
        });

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


        foreach ($monthPeriods as $month) {
            $monthString = $month->startOfMonth()->format("Y-m-d");
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $videoStatisticsQuery = VideoStatisticsDaily::when($channel, function ($query, $channel) {
                    return $query->where('channel_id', $channel->id);
                })
                ->whereDate('date', '>=', $from_day)
                ->whereDate('date', '<=', $to_day)->get();

            $channelStatisticsQuery = channelStatisticsDaily::when($channel, function ($query, $channel) {
                    return $query->where('channel_id', $channel->id);
                })
                ->whereDate('date', '>=', $from_day)
                ->whereDate('date', '<=', $to_day)->get();

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

        foreach ($periods as $day) {

            $videoStatisticsQuery = VideoStatisticsDaily::when($channel, function ($query, $channel) {
                    return $query->where('channel_id', $channel->id);
                })
                ->whereDate('date', $day->format('Y-m-d'))->get();

            $channelStatisticsQuery = channelStatisticsDaily::when($channel, function ($query, $channel) {
                    return $query->where('channel_id', $channel->id);
                })
                ->whereDate('date', $day->format('Y-m-d'))->get();

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
}
