<?php

namespace App\Listeners\Messages;

use App\Events\Messages\MessageRepliedByUser;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\Models\User;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnMessageRepliedByUser
{

    public function handle(MessageRepliedByUser $event)
    {
        $message = $event->message;
        $parentMessage = $event->parentMessage;

        $admins = User::admins()->get();

        TCNotification::Send($admins, new GeneralNotification(
            Notification::TYPE_REPLY_MESSAGE,
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            ['message' => MessageItem::make($message->load(['user', 'department']))],
            [
                'entity_type' => get_class($message),
                'entity_id' => $message->id,
            ]
        ));

        return true;
    }
}
