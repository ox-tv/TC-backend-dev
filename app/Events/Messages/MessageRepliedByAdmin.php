<?php

namespace App\Events\Messages;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRepliedByAdmin
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $parentMessage;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Message $message, Message $parentMessage)
    {
        $this->message = $message;
        $this->parentMessage = $parentMessage;
    }

}
