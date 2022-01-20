<?php

namespace App\Events\Channels;

use App\Models\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelImportRequestCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channel;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

}
