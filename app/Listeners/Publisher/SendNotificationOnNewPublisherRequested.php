<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\NewPublisherRequested;
use App\Events\VideoViewed;
use App\Http\Resources\Message\MessageItem;
use App\Http\Resources\User\UserResource;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserMeta;
use App\Notifications\NewPublisherRequest;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnNewPublisherRequested
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(NewPublisherRequested $event)
    {
        $user = $event->user;
        $message = $event->message;
        $channelNameMeta = $user->meta()
            ->where('key', UserMeta::REQUESTED_CHANNEL_NAME)->first();

        $admins = User::admins()->get();

        TCNotification::send($admins, new NewPublisherRequest(
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
                'user' => UserResource::make($user),
                'channel_name' => $channelNameMeta->value ?? ""
            ],
            get_class($message),
            $message->id
        ));

        return true;
    }
}
