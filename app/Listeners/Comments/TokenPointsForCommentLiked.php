<?php

namespace App\Listeners\Comments;

use App\Events\CommentLiked;
use App\Events\Comments\CommentCreated;
use App\Events\VideoViewed;
use App\Models\Channel2StatisticsDaily;
use App\Models\Logs\LogCommentLikedOnce;
use App\Models\TokenPoint;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;

class TokenPointsForCommentLiked
{
    private $tokenPointRepository;

    public function __construct(TokenPointRepository $tokenPointRepository)
    {
        $this->tokenPointRepository = $tokenPointRepository;
    }

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(CommentLiked $event)
    {
        $user = $event->user;
        $comment = $event->comment;
        $commentOwner = $comment->user()->first();
        $likeAmount = $event->likeAmount;

        if ($likeAmount != 1 || LogCommentLikedOnce::where('comment_id', $comment->id)->where('user_id', $user->id)->exists() || $comment->user_id == $user->id){
            return true;
        }

        $this->tokenPointRepository->add([
            'user_id' => $comment->user_id,
            'type' => $commentOwner->is_hero? TokenPoint::TYPE_LIKED_COMMENT_AS_HERO : TokenPoint::TYPE_LIKED_COMMENT,
            'amount' => $commentOwner->is_hero? config('points.token.liked_comment_as_hero') : config('points.token.liked_comment'),
        ]);

        return true;
    }
}
