<?php

namespace App\Listeners\Comments;

use App\Events\Comments\CommentCreated;
use App\Models\TokenPoint;
use App\Repository\Eloquent\TokenPointRepository;

class TokenPointsForCommentCreated
{
    private $tokenPointRepository;

    public function __construct(TokenPointRepository $tokenPointRepository)
    {
        $this->tokenPointRepository = $tokenPointRepository;
    }

    public function handle(CommentCreated $event)
    {
        $comment = $event->comment;
        $video = $comment->video()->first();
        $parentComment = $comment->parent()->first();

        if (!$parentComment || $video->user_id != $comment->user_id || $parentComment->user_id == $comment->user_id){
            return true;
        }

        $pointAmount = config('points.token.answer_a_comment');

        $this->tokenPointRepository->add([
            'user_id' => $comment->user_id,
            'type' => TokenPoint::TYPE_ANSWER_A_COMMENT,
            'amount' => $pointAmount,
        ]);

        return true;
    }
}
