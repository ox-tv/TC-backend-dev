<?php

use App\Models\Video;

class VideosChannelIdRefactor
{
    public function run()
    {
        Video::withTrashed()->get()->each(function ($video) {
            $channel = $video->channels()->withTrashed()->first();
            $video->channel_id = $channel->id;
            $video->save();
        });
    }
}