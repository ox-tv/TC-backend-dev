<?php

namespace App\Listeners\Comments;

use App\Events\CommentLiked;
use App\Models\LoyaltyPoint;
use App\Repository\Eloquent\LoyaltyPointRepository;
use Carbon\Carbon;

class LoyaltyPointsForCommentLiked
{
    private $loyaltyPointRepository;

    public function __construct(LoyaltyPointRepository $loyaltyPointRepository)
    {
        $this->loyaltyPointRepository = $loyaltyPointRepository;
    }

    public function handle(CommentLiked $event)
    {
        $user = $event->user;
        $comment = $event->comment;
        $likeAmount = $event->likeAmount;

        if ($user->is_hero){
            $pointsPerLiked = config('points.loyalty.per_comment_liked_as_hero');
        }else{
            $pointsPerLiked = config('points.loyalty.per_comment_liked_as_non_hero');
        }

        $this->loyaltyPointRepository->add([
            'user_id' => $user->id,
            'activated_at' => Carbon::now(),
            'type' => LoyaltyPoint::TYPE_COMMENT_LIKED,
            'amount' => $pointsPerLiked * $likeAmount,
        ]);

        return true;
    }
}
