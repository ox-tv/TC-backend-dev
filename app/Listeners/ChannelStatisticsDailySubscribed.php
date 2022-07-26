<?php

namespace App\Listeners;

use App\Events\ChannelSubscribed;
use App\Events\VideoLiked;
use App\Models\ChannelStatisticsDaily;
use App\Models\UserVideo;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ChannelStatisticsDailySubscribed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  VideoLiked  $event
     * @return void
     */
    public function handle(ChannelSubscribed $event)
    {
        $channel = $event->channel;
        $user = $event->user;
        $subscribersCount = $event->subscribersCount;
        $unSubscribersCount = $event->unSubscribersCount;


        $statistics = ChannelStatisticsDaily::firstOrNew([
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);


        $statistics->subscribers_total += $subscribersCount;
        $statistics->unsubscribers_total += $unSubscribersCount;

        if($user && $user->is_hero){
            $statistics->subscribers_hero += $subscribersCount;
            $statistics->unsubscribers_hero += $unSubscribersCount;
        }else{
            $statistics->subscribers_non_hero += $subscribersCount;
            $statistics->unsubscribers_non_hero += $unSubscribersCount;
        }

        $statistics->save();

        return $statistics;
    }
}
