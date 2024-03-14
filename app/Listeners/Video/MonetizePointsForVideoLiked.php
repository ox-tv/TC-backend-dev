<?php

namespace App\Listeners\Video;

use App\Events\VideoLiked;
use App\Models\MonetizePoint;
use App\Models\Video;
use App\Repository\Eloquent\MonetizePointRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MonetizePointsForVideoLiked
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
    public function handle(VideoLiked $event)
    {
        $video = $event->video;
        $user = $event->user;
        $likeAmount = $event->likeAmount;
        $dislikeAmount = $event->dislikeAmount;

        $channel = $video->channel;

        if($user && $user->is_hero){
            $pointsPerLikeVideo = config('points.monetize.per_like_video_as_hero');
            $pointsPerDisLikeVideo = config('points.monetize.per_dislike_video_as_hero');
        }else{
            $pointsPerLikeVideo = config('points.monetize.per_like_video_as_non_hero');
            $pointsPerDisLikeVideo = config('points.monetize.per_dislike_video_as_non_hero');
        }

        // Check channel is qualified
        if (!$channel->monetization_qualified_at || $channel->monetization_qualified_at > Carbon::now()){
            return 0;
        }

        // Calc point and add to DB
        $point = ($pointsPerLikeVideo * $likeAmount);
        $point += (-1 * $pointsPerDisLikeVideo * $dislikeAmount);

        $this->monetizePointRepository->add([
            'channel_id' => $channel->id,
            'related_to_type' => Video::class,
            'related_to_id' => $video->id,
            'activated_at' => Carbon::now(),
            'type' => MonetizePoint::TYPE_VIDEO_LIKED,
            'amount' => $point,
            'monetization_multiplier' => $channel->monetization_multiplier,
        ], [
            'channel_id',
            'related_to_type',
            'related_to_id',
            'type',
            'date',
        ]);
    }
}
