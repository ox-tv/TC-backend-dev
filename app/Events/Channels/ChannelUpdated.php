<?php

namespace App\Events\Channels;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $oldChannel;
    public $channel;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Video $oldChannel, Video $channel)
    {
        $this->oldChannel = $oldChannel;
        $this->channel = $channel;
    }

}
