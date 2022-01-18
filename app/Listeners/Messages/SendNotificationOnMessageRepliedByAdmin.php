<?php

namespace App\Listeners\Messages;

use App\Events\Messages\MessageCreatedByAdmin;
use App\Events\Messages\MessageRepliedByAdmin;
use App\Events\VideoViewed;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\Notifications\NewMessage;
use App\Notifications\ReplyMessage;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnMessageRepliedByAdmin
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(MessageRepliedByAdmin $event)
    {
        $message = $event->message;
        $parentMessage = $event->parentMessage;

        TCNotification::send($parentMessage->users, new ReplyMessage(
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
