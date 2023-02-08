<?php

namespace App\Repository\Eloquent;

use App\Models\Channel;
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
}
