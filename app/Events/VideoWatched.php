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

class VideoWatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $video;
    public $user;
    public $startTime;
    public $endTime;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Video $video, $user, $startTime, $endTime)
    {
        $this->video = $video;
        $this->user = $user;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

}
