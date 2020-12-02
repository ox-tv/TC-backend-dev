<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentDislike;
use App\Http\Requests\CommentLike;
use App\Models\Comment;
use App\Models\CommentUser;
use Illuminate\Support\Facades\Auth;

class CommentUserRelationController extends Controller
{
    public function like(CommentLike $request, Comment $comment){

        $user = Auth::user();

        $comment->dislikedBy()->detach($user->id);

        $comment->likedBy()->attach($user->id, ['relation' => CommentUser::LIKED_RELATION]);

    }

    public function dislike(CommentDislike $request, Comment $comment){

        $user = Auth::user();

        $comment->likedBy()->detach($user->id);

        $comment->dislikedBy()->attach($user->id, ['relation' => CommentUser::DISLIKED_RELATION]);

    }
}
