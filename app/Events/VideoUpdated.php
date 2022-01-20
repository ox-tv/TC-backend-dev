<?php

namespace App\Events;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $oldVideo;
    public $video;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Video $oldVideo, Video $video)
    {
        $this->oldVideo = $oldVideo;
        $this->video = $video;
    }

}
