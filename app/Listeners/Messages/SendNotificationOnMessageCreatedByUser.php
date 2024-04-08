<?php

namespace App\Listeners\Messages;

use App\Events\Messages\MessageCreatedByUser;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\Models\User;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnMessageCreatedByUser
{

    public function handle(MessageCreatedByUser $event)
    {
        $message = $event->message;
        $admins = User::admins()->get();

        TCNotification::Send($admins, new GeneralNotification(
            Notification::TYPE_NEW_MESSAGE,
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
