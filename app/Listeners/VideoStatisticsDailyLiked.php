<?php

namespace App\Listeners;

use App\Events\VideoLiked;
use App\Models\Channel2StatisticsDaily;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class VideoStatisticsDailyLiked
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
    public function handle(VideoLiked $event)
    {
        $video = $event->video;
        $channel = $video->channel;
        $user = $event->user;
        $likeAmount = $event->likeAmount;
        $dislikeAmount = $event->dislikeAmount;
        $pointsPerLikeHero = config('general.points.per_like_hero');
        $pointsPerLikeNonHero = config('general.points.per_like_non_hero');
        $pointsPerDisLikeHero = config('general.points.per_dislike_hero');
        $pointsPerDisLikeNonHero = config('general.points.per_dislike_non_hero');


        $statistics = Channel2StatisticsDaily::firstOrNew([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);


        $statistics->likes_total += $likeAmount;
        $statistics->dislikes_total += $dislikeAmount;

        if($user && $user->is_hero){
            $statistics->likes_hero += $likeAmount;
            $statistics->dislikes_hero += $dislikeAmount;
        }else{
            $statistics->likes_non_hero += $likeAmount;
            $statistics->dislikes_non_hero += $dislikeAmount;
        }

        $statistics->save();

        return $statistics;
    }
}
