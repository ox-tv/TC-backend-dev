<?php

namespace App\Events;

use App\Models\User;
use App\Models\Video;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCommented
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $video;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Video $video, $user = null)
    {
        $this->video = $video;
        $this->user = $user;
    }

}
