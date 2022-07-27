<?php

namespace App\Http\Controllers;

use App\Events\CommentLiked;
use App\Http\Requests\CommentDislike;
use App\Http\Requests\CommentLike;
use App\Models\Comment;
use App\Models\CommentUser;
use App\Models\Scopes\WhereParentNullScope;
use Illuminate\Support\Facades\Auth;

class CommentUserRelationController extends Controller
{
    public function like($id)
    {
        $comment = Comment::whereId($id)->withoutGlobalScope(WhereParentNullScope::class)->firstOrFail();

        $userId = Auth::id();

        $isDisliked = CommentUser::where('comment_id', $id)
            ->where('user_id', $userId)
            ->where('relation', CommentUser::DISLIKED_RELATION)
            ->first();

        $isLiked = CommentUser::where('comment_id', $id)
            ->where('user_id', $userId)
            ->where('relation', CommentUser::LIKED_RELATION)
            ->first();

        if($isDisliked){

            CommentUser::where('comment_id', $id)
                ->where('user_id', $userId)
                ->where('relation', CommentUser::DISLIKED_RELATION)
                ->update(['relation' => CommentUser::LIKED_RELATION]);

        }else if($isLiked){

            CommentUser::where('comment_id', $id)
                ->where('user_id', $userId)
                ->where('relation', CommentUser::LIKED_RELATION)
                ->delete();

        }else{
            CommentUser::create([
                'user_id' => $userId,
                'comment_id' => $id,
                'relation' => CommentUser::LIKED_RELATION
            ]);
        }

        event(new CommentLiked($comment, auth('api')->user(), $isLiked?-1:1, $isDisliked?-1:0));

        return response()->json([
            'is_liked' => $comment->is_liked,
            'is_disliked' => $comment->is_disliked,
            'likes_count' => CommentUser::where('comment_id', $id)->where('relation', CommentUser::LIKED_RELATION)->count(),
            'dislikes_count' => CommentUser::where('comment_id', $id)->where('relation', CommentUser::DISLIKED_RELATION)->count(),
        ]);

    }

    public function dislike($id)
    {
        $comment = Comment::whereId($id)->withoutGlobalScope(WhereParentNullScope::class)->firstOrFail();

        $userId = Auth::id();

        $isDisliked = CommentUser::where('comment_id', $id)
            ->where('user_id', $userId)
            ->where('relation', CommentUser::DISLIKED_RELATION)
            ->first();

        $isLiked = CommentUser::where('comment_id', $id)
            ->where('user_id', $userId)
            ->where('relation', CommentUser::LIKED_RELATION)
            ->first();

        if($isLiked){

            CommentUser::where('comment_id', $id)
                ->where('user_id', $userId)
                ->where('relation', CommentUser::LIKED_RELATION)
                ->update(['relation' => CommentUser::DISLIKED_RELATION]);

        }else if($isDisliked){

            CommentUser::where('comment_id', $id)
                ->where('user_id', $userId)
                ->where('relation', CommentUser::DISLIKED_RELATION)
                ->delete();

        }else{

            CommentUser::create([
                'user_id' => $userId,
                'comment_id' => $id,
                'relation' => CommentUser::DISLIKED_RELATION
            ]);
        }

        return response()->json([
            'is_liked' => $comment->is_liked,
            'is_disliked' => $comment->is_disliked,
            'likes_count' => CommentUser::where('comment_id', $id)->where('relation', CommentUser::LIKED_RELATION)->count(),
            'dislikes_count' => CommentUser::where('comment_id', $id)->where('relation', CommentUser::DISLIKED_RELATION)->count(),
        ]);
    }

    public function remember($id)
    {
        $comment = Comment::whereId($id)->withoutGlobalScope(WhereParentNullScope::class)->firstOrFail();

        $userId = auth('api')->id();

        $isRemembered = CommentUser::where('comment_id', $id)
            ->where('user_id', $userId)
            ->where('relation', CommentUser::REMEMBERED_RELATION)
            ->first();

        if($isRemembered){
            CommentUser::where('comment_id', $id)
                ->where('user_id', $userId)
                ->where('relation', CommentUser::REMEMBERED_RELATION)
                ->delete();
        }else{
            CommentUser::create([
                'user_id' => $userId,
                'comment_id' => $id,
                'relation' => CommentUser::REMEMBERED_RELATION
            ]);
        }

        return response()->json([
            'is_remembered' => !$isRemembered,
        ]);
    }

    public function unrememberAll()
    {
        $userId = auth('api')->id();

        CommentUser::where('user_id', $userId)->where('relation', CommentUser::REMEMBERED_RELATION)->delete();

        return response()->json([
            'status' => 'ok',
        ]);
    }
}
