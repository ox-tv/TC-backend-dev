<?php

namespace App\Listeners\Messages;

use App\Events\Messages\MessageRepliedByAdmin;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnMessageRepliedByAdmin
{

    public function handle(MessageRepliedByAdmin $event)
    {
        $message = $event->message;
        $parentMessage = $event->parentMessage;

        TCNotification::Send($parentMessage->users, new GeneralNotification(
            Notification::TYPE_REPLY_MESSAGE,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            ['message' => MessageItem::make($message->load(['user', 'department']))],
            [
                'entity_type' => get_class($message),
                'entity_id' => $message->id,
            ]
        ));

        return true;
    }
}
