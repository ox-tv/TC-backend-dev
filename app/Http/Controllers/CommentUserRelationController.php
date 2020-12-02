<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentDislike;
use App\Http\Requests\CommentLike;
use App\Http\Requests\VideoDislike;
use App\Http\Requests\VideoLike;
use App\Http\Requests\VideoStore;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoItem;
use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentUser;
use App\Models\Video;
use App\Models\UserVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
