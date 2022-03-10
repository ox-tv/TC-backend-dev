<?php

namespace App\Events\Publisher;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublisherRequestRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $reason;
    public $parentMessage;
    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $reason, Message $parentMessage, Message $message)
    {
        $this->user = $user;
        $this->reason = $reason;
        $this->parentMessage = $parentMessage;
        $this->message = $message;
    }

}
