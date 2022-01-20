<?php

namespace App\Listeners\Messages;

use App\Events\Messages\MessageCreatedByAdmin;
use App\Events\Messages\MessageRepliedByAdmin;
use App\Events\Messages\MessageRepliedByUser;
use App\Events\VideoViewed;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Notifications\ReplyMessage;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnMessageRepliedByUser
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(MessageRepliedByUser $event)
    {
        $message = $event->message;
        $parentMessage = $event->parentMessage;

        $admins = User::admins()->get();

        TCNotification::send($admins, new ReplyMessage(
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
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
