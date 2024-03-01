<?php

namespace App\Listeners\Channels;

use App\Events\ChannelSubscribed;
use App\Events\VideoLiked;
use App\Models\MonetizePoint;
use App\Repository\Eloquent\MonetizePointRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MonetizePointsForChannelSubscribed
{
    private $monetizePointRepository;

    public function __construct(MonetizePointRepository $monetizePointRepository)
    {
        $this->monetizePointRepository = $monetizePointRepository;
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

        if($user && $user->is_hero){
            $pointsPerChannelSubscribed = config('points.monetize.per_subscribe_channel_as_hero');
        }else{
            $pointsPerChannelSubscribed = config('points.monetize.per_subscribe_channel_as_non_hero');
        }

        // Check channel is qualified
        if (!$channel->monetization_qualified_at || $channel->monetization_qualified_at > Carbon::now()){
            return;
        }

        // Calc point and add to DB
        $point = ($pointsPerChannelSubscribed * $subscribersCount);
        $point += (-1 * $pointsPerChannelSubscribed * $unSubscribersCount);

        $this->monetizePointRepository->add([
            'channel_id' => $channel->id,
            'activated_at' => Carbon::now(),
            'type' => MonetizePoint::TYPE_SUBSCRIPTION,
            'amount' => $point,
        ], [
            'channel_id',
            'type',
            'date',
        ]);

        return;
    }
}
