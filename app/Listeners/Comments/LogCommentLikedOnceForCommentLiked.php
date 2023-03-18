<?php

namespace App\Listeners\Comments;

use App\Events\CommentLiked;
use App\Models\Logs\LogCommentLikedOnce;
use App\Models\LoyaltyPoint;
use App\Repository\Eloquent\LoyaltyPointRepository;
use Carbon\Carbon;

class LogCommentLikedOnceForCommentLiked
{
    public function handle(CommentLiked $event)
    {
        $user = $event->user;
        $comment = $event->comment;
        $likeAmount = $event->likeAmount;

        if ($likeAmount != 1 || LogCommentLikedOnce::where('comment_id', $comment->id)->where('user_id', $user->id)->exists()){
            return true;
        }

        $model = new LogCommentLikedOnce();
        $model->comment_id = $comment->id;
        $model->user_id = $user->id;
        $model->save();

        return true;
    }
}
