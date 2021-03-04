<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoDislike;
use App\Http\Requests\VideoLike;
use App\Http\Requests\VideoStore;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoItem;
use App\Http\Resources\VideoSummaryItem;
use App\Models\Category;
use App\Models\Video;
use App\Models\UserVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserVideoRelationController extends Controller
{
    public function like(Video $video){

        $userRelation = 'like';

        $userId = Auth::id();

        $isDisliked = $video->dislikedBy()->find($userId);
        $isLiked = $video->likedBy()->find($userId);

        if($isDisliked){

            $video->dislikedBy()->detach($userId);
            $video->likedBy()->attach($userId, ['relation' => UserVideo::LIKED_RELATION]);

        }else if($isLiked){

            $video->likedBy()->detach($userId, ['relation' => UserVideo::LIKED_RELATION]);

            $userRelation = null;

        }else{

            $video->likedBy()->attach($userId, ['relation' => UserVideo::LIKED_RELATION]);

        }

        return response()->json([
            'is_liked' => $video->is_liked,
            'is_disliked' => $video->is_disliked,
            'likes_count' => $video->likedBy()->count(),
            'dislikes_count' => $video->dislikedBy()->count(),
        ]);

    }

    public function dislike(Video $video){

        $userRelation = 'dislike';

        $userId = Auth::id();

        $isDisliked = $video->dislikedBy()->find($userId);
        $isLiked = $video->likedBy()->find($userId);

        if($isLiked){

            $video->likedBy()->detach($userId);
            $video->dislikedBy()->attach($userId, ['relation' => UserVideo::DISLIKED_RELATION]);

        }else if($isDisliked){

            $video->dislikedBy()->detach($userId, ['relation' => UserVideo::DISLIKED_RELATION]);

            $userRelation = null;

        }else{

            $video->dislikedBy()->attach($userId, ['relation' => UserVideo::DISLIKED_RELATION]);

        }

        return response()->json([
            'is_liked' => $video->is_liked,
            'is_disliked' => $video->is_disliked,
            'likes_count' => $video->likedBy()->count(),
            'dislikes_count' => $video->dislikedBy()->count(),
        ]);

    }
}
