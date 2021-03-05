<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentDislike;
use App\Http\Requests\CommentLike;
use App\Models\Comment;
use App\Models\CommentUser;
use Illuminate\Support\Facades\Auth;

class CommentUserRelationController extends Controller
{
    public function like(Comment $comment){

        $userId = Auth::id();

        $isDisliked = $comment->dislikedBy()->find($userId);
        $isLiked = $comment->likedBy()->find($userId);

        if($isDisliked){

            $comment->dislikedBy()->detach($userId);
            $comment->likedBy()->attach($userId, ['relation' => CommentUser::LIKED_RELATION]);

        }else if($isLiked){

            $comment->likedBy()->detach($userId, ['relation' => CommentUser::LIKED_RELATION]);

            $userRelation = null;

        }else{

            $comment->likedBy()->attach($userId, ['relation' => CommentUser::LIKED_RELATION]);

        }

        return response()->json([
            'is_liked' => $comment->is_liked,
            'is_disliked' => $comment->is_disliked,
            'likes_count' => $comment->likedBy()->count(),
            'dislikes_count' => $comment->dislikedBy()->count(),
        ]);

    }

    public function dislike(Comment $comment){

        $userId = Auth::id();

        $isDisliked = $comment->dislikedBy()->find($userId);
        $isLiked = $comment->likedBy()->find($userId);

        if($isLiked){

            $comment->likedBy()->detach($userId);
            $comment->dislikedBy()->attach($userId, ['relation' => CommentUser::DISLIKED_RELATION]);

        }else if($isDisliked){

            $comment->dislikedBy()->detach($userId, ['relation' => CommentUser::DISLIKED_RELATION]);

            $userRelation = null;

        }else{

            $comment->dislikedBy()->attach($userId, ['relation' => CommentUser::DISLIKED_RELATION]);

        }

        return response()->json([
            'is_liked' => $comment->is_liked,
            'is_disliked' => $comment->is_disliked,
            'likes_count' => $comment->likedBy()->count(),
            'dislikes_count' => $comment->dislikedBy()->count(),
        ]);

    }
}
