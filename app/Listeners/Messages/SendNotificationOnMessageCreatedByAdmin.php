<?php

namespace App\Listeners\Messages;

use App\Events\Messages\MessageCreatedByAdmin;
use App\Events\VideoViewed;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnMessageCreatedByAdmin
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(MessageCreatedByAdmin $event)
    {
        $message = $event->message;
        $users = $message->users;

        TCNotification::Send($users, new GeneralNotification(
            Notification::TYPE_NEW_MESSAGE,
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
