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

        $isDisliked = $comment->dislikedBy()->find($userId);
        $isLiked = $comment->likedBy()->find($userId);

        if($isDisliked){

            CommentUser::where('comment_id', $id)
                ->where('user_id', $userId)
                ->where('relation', CommentUser::DISLIKED_RELATION)
                ->update(['relation' => CommentUser::LIKED_RELATION]);

//            $comment->dislikedBy->wherePivot('relation', CommentUser::DISLIKED_RELATION)->sync([$userId => ['relation' => CommentUser::LIKED_RELATION]]);
//            $comment->dislikedBy()->wherePivot('relation', CommentUser::DISLIKED_RELATION)->detach($userId);
//            $comment->likedBy()->attach($userId, ['relation' => CommentUser::LIKED_RELATION]);

        }else if($isLiked){

            $comment->likedBy()->detach($userId, ['relation' => CommentUser::LIKED_RELATION]);

        }else{

            $comment->likedBy()->attach($userId, ['relation' => CommentUser::LIKED_RELATION]);

        }

        event(new CommentLiked($comment, auth('api')->user(), $isLiked?-1:1, $isDisliked?-1:0));

        return response()->json([
            'is_liked' => $comment->is_liked,
            'is_disliked' => $comment->is_disliked,
            'likes_count' => $comment->likedBy()->count(),
            'dislikes_count' => $comment->dislikedBy()->count(),
        ]);

    }

    public function dislike($id)
    {
        $comment = Comment::whereId($id)->withoutGlobalScope(WhereParentNullScope::class)->firstOrFail();

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

    public function remember($id)
    {
        $comment = Comment::whereId($id)->withoutGlobalScope(WhereParentNullScope::class)->firstOrFail();

        $userId = auth('api')->id();

        $isRemembered = $comment->rememberedBy()->whereUserId($userId)->exists();

        if($isRemembered){
            $comment->rememberedBy()->detach($userId, ['relation' => CommentUser::REMEMBERED_RELATION]);
        }else{
            $comment->rememberedBy()->attach($userId, ['relation' => CommentUser::REMEMBERED_RELATION]);
        }

        return response()->json([
            'is_remembered' => !$isRemembered,
        ]);
    }

    public function unrememberAll()
    {
        $user = auth('api')->user();

        CommentUser::whereUserId($user->id)->where('relation', CommentUser::REMEMBERED_RELATION)->delete();

        return response()->json([
            'status' => 'ok',
        ]);
    }
}
