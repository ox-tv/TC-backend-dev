<?php

namespace App\Listeners\Comments;

use App\Events\CommentLiked;
use App\Events\VideoLiked;
use App\Models\Comment;
use App\Models\LoyaltyPoint;
use App\Models\UserStatisticsDaily;
use App\Repository\Eloquent\LoyaltyPointRepository;
use Carbon\Carbon;

class LoyaltyPointsForCommentLiked
{
    private $loyaltyPointRepository;

    public function __construct(LoyaltyPointRepository $loyaltyPointRepository)
    {
        $this->loyaltyPointRepository = $loyaltyPointRepository;
    }

    /**
     * Handle the event.
     *
     * @param  VideoLiked  $event
     * @return void
     */
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
            'related_to_type' => Comment::class,
            'related_to_id' => $comment->id,
            'activated_at' => Carbon::now(),
            'type' => LoyaltyPoint::TYPE_COMMENT_LIKED,
            'amount' => $pointsPerLiked * $likeAmount,
        ], [
            'user_id',
            'related_to_type',
            'related_to_id',
            'type',
            'date',
        ]);

        return true;
    }
}
