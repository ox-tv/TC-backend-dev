<?php

namespace App\Listeners;

use App\Events\Comments\CommentCreated;
use App\Models\Channel2StatisticsDaily;
use Carbon\Carbon;

class VideoStatisticsDailyCommented
{
    public function handle(CommentCreated $event)
    {
        $comment = $event->comment;
        $user = $comment->user()->first();
        $video = $comment->video()->first();
        $channel = $video->channel;

        $statistics = Channel2StatisticsDaily::firstOrNew([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->comments_total += 1;

        if($user && $user->is_hero){
            $statistics->comments_hero += 1;
        }else{
            $statistics->comments_non_hero += 1;
        }

        $statistics->save();

        return $statistics;
    }
}
