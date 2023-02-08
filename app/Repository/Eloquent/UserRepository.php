<?php

namespace App\Repository\Eloquent;

use App\Models\Channel;
use App\Models\UserVideo;
use App\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserRepository
{
    public function subscribedChannelIds($userId)
    {
        return Cache::remember("user{$userId}_subscribedChannelIds", 24 * 60 * 60 , function () use ($userId) {
            return DB::table('channel_user')
                ->where('user_id', $userId)
                ->pluck('channel_id')->toArray();
        });
    }

    public function subscribedChannelsCount($userId)
    {
        return count($this->subscribedChannelIds($userId));
    }

    public function bookmarkedVideoIds($userId)
    {
        return Cache::remember("user{$userId}_bookmarkedVideoIds", 24 * 60 * 60 , function () use ($userId) {
            return UserVideo::where('relation', UserVideo::BOOKMARKED_RELATION)
                ->where('user_id', $userId)
                ->pluck('video_id')->toArray();
        });
    }

    public function bookmarkedVideosCount($userId)
    {
        return count($this->bookmarkedVideoIds($userId));
    }
}
