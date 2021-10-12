<?php

namespace App\Listeners;

use App\Events\VideoLiked;
use App\Models\UserStatisticsDaily;
use App\Models\UserVideo;
use App\Models\VideoStatisticsDaily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class VideoLikedDataForUserStatisticsDaily
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
        $user = $event->user;
        $likeAmount = $event->likeAmount;
        $dislikeAmount = $event->dislikeAmount;

        if (!$user) {
            return;
        }

        $statistics = UserStatisticsDaily::firstOrNew([
            'user_id' => $user->id,
            'date' => date('Y-m-d'),
        ]);

        $statistics->video_likes_count_total += $likeAmount;

        if($user->is_hero){
            $statistics->video_likes_count_as_hero += $likeAmount;
        }else{
            $statistics->video_likes_count_as_non_hero += $likeAmount;
        }

        $statistics->save();

        return $statistics;
    }
}
