<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\Video;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentLiked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;
    public $user;
    public $likeAmount;
    public $dislikeAmount;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Comment $comment, $user, $likeAmount, $dislikeAmount)
    {
        $this->comment = $comment;
        $this->user = $user;
        $this->likeAmount = $likeAmount;
        $this->dislikeAmount = $dislikeAmount;
    }

}
