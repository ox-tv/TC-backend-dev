<?php

namespace App\Listeners;

use App\Events\CommentLiked;
use App\Events\VideoLiked;
use App\Models\UserStatisticsDaily;
use App\Models\UserVideo;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CommentLikedDataForUserStatisticsDaily
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
    public function handle(CommentLiked $event)
    {
        $user = $event->user;
        $comment = $event->comment;
        $likeAmount = $event->likeAmount;

        // Data for $user
        $statistics = UserStatisticsDaily::firstOrNew([
            'user_id' => $user->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->comment_likes_count_total += $likeAmount;

        if($user->is_hero){
            $statistics->comment_likes_count_as_hero += $likeAmount;
        }else{
            $statistics->comment_likes_count_as_non_hero += $likeAmount;
        }

        $statistics->calcPoints();

        $statistics->save();


        // Data for $comment->user
        $statistics = UserStatisticsDaily::firstOrNew([
            'user_id' => $comment->user->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->comment_liked_count_total += $likeAmount;

        if($comment->user->is_hero){
            $statistics->comment_liked_count_as_hero += $likeAmount;
        }else{
            $statistics->comment_liked_count_as_non_hero += $likeAmount;
        }

        $statistics->calcPoints();

        $statistics->save();

        return true;
    }
}
