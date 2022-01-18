<?php

namespace App\Listeners\Messages;

use App\Events\Messages\MessageCreatedByAdmin;
use App\Events\VideoViewed;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\Notifications\NewMessage;
use App\Notifications\TCNotification\TCNotification;

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

        TCNotification::send($users, new NewMessage(
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ],
            get_class($message),
            $message->id
        ));

        return true;
    }
}
