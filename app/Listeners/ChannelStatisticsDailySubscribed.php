<?php

namespace App\Listeners;

use App\Events\ChannelSubscribed;
use App\Events\VideoLiked;
use App\Models\Channel2StatisticsDaily;
use Carbon\Carbon;

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


        $statistics = Channel2StatisticsDaily::firstOrNew([
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
