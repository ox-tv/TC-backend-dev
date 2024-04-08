<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\NewPublisherRequested;
use App\Http\Resources\Message\MessageItem;
use App\Http\Resources\User\UserResource;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserMeta;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnNewPublisherRequested
{

    public function handle(NewPublisherRequested $event)
    {
        $user = $event->user;
        $message = $event->message;
        $channelNameMeta = $user->meta()
            ->where('key', UserMeta::REQUESTED_CHANNEL_NAME)->first();

        $admins = User::admins()->get();

        TCNotification::Send($admins, new GeneralNotification(
            Notification::TYPE_NEW_PUBLISHER_REQUEST,
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
                'user' => UserResource::make($user),
                'channel_name' => $channelNameMeta->value ?? ""
            ],
            [
                'entity_type' => get_class($message),
                'entity_id' => $message->id,
            ]
        ));

        return true;
    }
}
