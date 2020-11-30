<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoDislike;
use App\Http\Requests\VideoLike;
use App\Http\Requests\VideoStore;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoItem;
use App\Models\Category;
use App\Models\Video;
use App\Models\VideoUser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserVideoRelationController extends Controller
{
    public function like(VideoLike $request, Video $video){

        $user = Auth::user();

        $video->likedBy()->attach($user->id, ['relation' => VideoUser::LIKED_RELATION]);

    }

    public function dislike(VideoDislike $request, Video $video){

        $user = Auth::user();

        $video->dislikedBy()->attach($user->id, ['relation' => VideoUser::DISLIKED_RELATION]);

    }
}
