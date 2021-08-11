<?php

namespace App\Events;

use App\Models\Video;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelSubscribed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channel;
    public $user;
    public $subscribersCount;
    public $unSubscribersCount;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Channel $channel, $user, $subscribersCount, $unSubscribersCount)
    {
        $this->channel = $channel;
        $this->user = $user;
        $this->subscribersCount = $subscribersCount;
        $this->unSubscribersCount = $unSubscribersCount;
    }

}
