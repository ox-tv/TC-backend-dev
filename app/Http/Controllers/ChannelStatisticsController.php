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
        if ($request->is('api/admin/*')){

            $channel = Channel::where(function ($query) use ($idOrSlug){
                $query->whereId($idOrSlug)->orWhere('slug', $idOrSlug);
            })->firstOrFail();

        }else{
            $channel = auth('api')->user()->channel;
        }


        $filters = $request->get('filters', []);
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');


        // Video Statistics by channel
        $videoStatisticsQuery = VideoStatisticsDaily::where([
            'channel_id' => $channel->id
        ]);

        if($fromFilter){
            $videoStatisticsQuery->where('date', '>=', $fromFilter);
        }

        if($toFilter){
            $videoStatisticsQuery->where('date', '<=', $toFilter);
        }


        // channel Statistics
        $channelStatisticsQuery = channelStatisticsDaily::where([
            'channel_id' => $channel->id
        ]);

        if($fromFilter){
            $channelStatisticsQuery->where('date', '>=', $fromFilter);
        }

        if($toFilter){
            $channelStatisticsQuery->where('date', '<=', $toFilter);
        }

        return response()->json([
            'points' => $videoStatisticsQuery->sum('points'),
            'views_hero' => $videoStatisticsQuery->sum('views_hero'),
            'views_non_hero' => $videoStatisticsQuery->sum('views_non_hero'),
            'views_total' => $videoStatisticsQuery->sum('views_total'),
            'likes_hero' => $videoStatisticsQuery->sum('likes_hero'),
            'likes_non_hero' => $videoStatisticsQuery->sum('likes_non_hero'),
            'likes_total' => $videoStatisticsQuery->sum('likes_total'),
            'dislikes_hero' => $videoStatisticsQuery->sum('dislikes_hero'),
            'dislikes_non_hero' => $videoStatisticsQuery->sum('dislikes_non_hero'),
            'dislikes_total' => $videoStatisticsQuery->sum('dislikes_total'),
            'subscribers_hero' => $channelStatisticsQuery->sum('subscribers_hero'),
            'subscribers_non_hero' => $channelStatisticsQuery->sum('subscribers_non_hero'),
            'subscribers_total' => $channelStatisticsQuery->sum('subscribers_total'),
            'unsubscribers_hero' => $channelStatisticsQuery->sum('unsubscribers_hero'),
            'unsubscribers_non_hero' => $channelStatisticsQuery->sum('unsubscribers_non_hero'),
            'unsubscribers_total' => $channelStatisticsQuery->sum('unsubscribers_total'),
            'upload_videos_total' => $channelStatisticsQuery->sum('upload_videos_total'),
        ]);
    }

    public function monthly(Request $request, $idOrSlug = null)
    {
        if ($request->is('api/admin/*')){

            $channel = Channel::where(function ($query) use ($idOrSlug){
                $query->whereId($idOrSlug)->orWhere('slug', $idOrSlug);
            })->firstOrFail();

        }else{
            $channel = auth('api')->user()->channel;
        }

        $statistics = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(12)->firstOfMonth());
        $to = Arr::get($filters, 'to', (Carbon::now())->firstOfMonth());
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);


        foreach ($monthPeriods as $month) {
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $videoStatisticsQuery = VideoStatisticsDaily::where('channel_id', $channel->id)
                ->whereDate('date', '>=', $from_day)
                ->whereDate('date', '<=', $to_day)->get();

            $channelStatisticsQuery = channelStatisticsDaily::where('channel_id', $channel->id)
                ->whereDate('date', '>=', $from_day)
                ->whereDate('date', '<=', $to_day)->get();

            $statistics[$month->format("Y-m")] = [
                'points' => $videoStatisticsQuery->sum('points'),
                'views_hero' => $videoStatisticsQuery->sum('views_hero'),
                'views_non_hero' => $videoStatisticsQuery->sum('views_non_hero'),
                'views_total' => $videoStatisticsQuery->sum('views_total'),
                'likes_hero' => $videoStatisticsQuery->sum('likes_hero'),
                'likes_non_hero' => $videoStatisticsQuery->sum('likes_non_hero'),
                'likes_total' => $videoStatisticsQuery->sum('likes_total'),
                'dislikes_hero' => $videoStatisticsQuery->sum('dislikes_hero'),
                'dislikes_non_hero' => $videoStatisticsQuery->sum('dislikes_non_hero'),
                'dislikes_total' => $videoStatisticsQuery->sum('dislikes_total'),
                'subscribers_hero' => $channelStatisticsQuery->sum('subscribers_hero'),
                'subscribers_non_hero' => $channelStatisticsQuery->sum('subscribers_non_hero'),
                'subscribers_total' => $channelStatisticsQuery->sum('subscribers_total'),
                'unsubscribers_hero' => $channelStatisticsQuery->sum('unsubscribers_hero'),
                'unsubscribers_non_hero' => $channelStatisticsQuery->sum('unsubscribers_non_hero'),
                'unsubscribers_total' => $channelStatisticsQuery->sum('unsubscribers_total'),
                'upload_videos_total' => $channelStatisticsQuery->sum('upload_videos_total'),
            ];
        }

        return $statistics;
    }

    public function daily(Request $request, $idOrSlug = null)
    {
        if ($request->is('api/admin/*')){

            $channel = Channel::where(function ($query) use ($idOrSlug){
                $query->whereId($idOrSlug)->orWhere('slug', $idOrSlug);
            })->firstOrFail();

        }else{
            $channel = auth('api')->user()->channel;
        }

        $statistics = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subDays(30)->format('Y-m-d'));
        $to = Arr::get($filters, 'to', (Carbon::now())->format('Y-m-d H:i:s'));


        $periods = CarbonPeriod::create($from, '1 day', $to);
        abort_unless(count($periods) <= 31, 400, 'timespan between from and to is more than 30 days');

        foreach ($periods as $day) {

            $videoStatisticsQuery = VideoStatisticsDaily::where('channel_id', $channel->id)
                ->whereDate('date', $day->format('Y-m-d'))->get();

            $channelStatisticsQuery = channelStatisticsDaily::where('channel_id', $channel->id)
                ->whereDate('date', $day->format('Y-m-d'))->get();

            $statistics[$day->format('Y-m-d')] = [
                'points' => $videoStatisticsQuery->sum('points'),
                'views_hero' => $videoStatisticsQuery->sum('views_hero'),
                'views_non_hero' => $videoStatisticsQuery->sum('views_non_hero'),
                'views_total' => $videoStatisticsQuery->sum('views_total'),
                'likes_hero' => $videoStatisticsQuery->sum('likes_hero'),
                'likes_non_hero' => $videoStatisticsQuery->sum('likes_non_hero'),
                'likes_total' => $videoStatisticsQuery->sum('likes_total'),
                'dislikes_hero' => $videoStatisticsQuery->sum('dislikes_hero'),
                'dislikes_non_hero' => $videoStatisticsQuery->sum('dislikes_non_hero'),
                'dislikes_total' => $videoStatisticsQuery->sum('dislikes_total'),
                'subscribers_hero' => $channelStatisticsQuery->sum('subscribers_hero'),
                'subscribers_non_hero' => $channelStatisticsQuery->sum('subscribers_non_hero'),
                'subscribers_total' => $channelStatisticsQuery->sum('subscribers_total'),
                'unsubscribers_hero' => $channelStatisticsQuery->sum('unsubscribers_hero'),
                'unsubscribers_non_hero' => $channelStatisticsQuery->sum('unsubscribers_non_hero'),
                'unsubscribers_total' => $channelStatisticsQuery->sum('unsubscribers_total'),
                'upload_videos_total' => $channelStatisticsQuery->sum('upload_videos_total'),
            ];
        }

        return $statistics;
    }
}
