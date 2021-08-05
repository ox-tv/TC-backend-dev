<?php

namespace App\Http\Controllers;

use App\CacheManagement\ChannelCacheManager;
use App\Events\VideoLiked;
use App\Models\Video;
use App\Models\UserVideo;
use Illuminate\Support\Facades\Auth;

class UserVideoRelationController extends Controller
{
    public function like(Video $video, ChannelCacheManager $channelCacheManager)
    {
        $userId = Auth::id();
        $channel = $video->channel;

        $isDisliked = $video->dislikedBy()->find($userId);
        $isLiked = $video->likedBy()->find($userId);

        if($isDisliked){

            $video->dislikedBy()->wherePivot('relation', UserVideo::DISLIKED_RELATION)->detach($userId);
            $video->likedBy()->attach($userId, ['relation' => UserVideo::LIKED_RELATION]);

            $channelCacheManager->addToChannelsMonthLikes($channel->id, -1, false);
            $channelCacheManager->addToChannelsMonthLikes($channel->id, 1);

        }else if($isLiked){

            $video->likedBy()->wherePivot('relation', UserVideo::LIKED_RELATION)->detach($userId);

            $channelCacheManager->addToChannelsMonthLikes($channel->id, 1, false);

            $userRelation = null;

        }else{

            $video->likedBy()->attach($userId, ['relation' => UserVideo::LIKED_RELATION]);
            $channelCacheManager->addToChannelsMonthLikes($channel->id, 1);
        }

        event(new VideoLiked($video, auth('api')->user(), $isLiked?-1:1, $isDisliked?-1:0));

        return response()->json([
            'is_liked' => $video->is_liked,
            'is_disliked' => $video->is_disliked,
            'likes_count' => $video->likedBy()->count(),
            'dislikes_count' => $video->dislikedBy()->count(),
        ]);

    }

    public function dislike(Video $video, ChannelCacheManager $channelCacheManager){

        $userId = Auth::id();
        $channel = $video->channel;

        $isDisliked = $video->dislikedBy()->find($userId);
        $isLiked = $video->likedBy()->find($userId);

        if($isLiked){

            $video->likedBy()->wherePivot('relation', UserVideo::LIKED_RELATION)->detach($userId);
            $video->dislikedBy()->attach($userId, ['relation' => UserVideo::DISLIKED_RELATION]);

            $channelCacheManager->addToChannelsMonthLikes($channel->id, 1, false);
            $channelCacheManager->addToChannelsMonthLikes($channel->id, -1);

        }else if($isDisliked){

            $video->dislikedBy()->wherePivot('relation', UserVideo::DISLIKED_RELATION)->detach($userId);
            $channelCacheManager->addToChannelsMonthLikes($channel->id, -1, false);

            $userRelation = null;

        }else{

            $video->dislikedBy()->attach($userId, ['relation' => UserVideo::DISLIKED_RELATION]);
            $channelCacheManager->addToChannelsMonthLikes($channel->id, -1);
        }

        event(new VideoLiked($video, auth('api')->user(), $isLiked?-1:0, $isDisliked?-1:1));

        return response()->json([
            'is_liked' => $video->is_liked,
            'is_disliked' => $video->is_disliked,
            'likes_count' => $video->likedBy()->count(),
            'dislikes_count' => $video->dislikedBy()->count(),
        ]);

    }

    public function bookmark(Video $video){

        $userId = Auth::id();

        $isBookmarked = $video->bookmarkedBy()->find($userId);

        $bookmarkStatus = null;

        if($isBookmarked){

            $video->bookmarkedBy()->wherePivot('relation', UserVideo::BOOKMARKED_RELATION)->detach($userId);
            $bookmarkStatus = false;

        }else{

            $video->bookmarkedBy()->attach($userId, ['relation' => UserVideo::BOOKMARKED_RELATION]);
            $bookmarkStatus = true;

        }

        return response()->json([
            'is_bookmarked' => $bookmarkStatus,
        ]);

    }
}
