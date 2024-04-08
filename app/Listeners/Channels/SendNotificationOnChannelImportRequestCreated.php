<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelImportRequestCreated;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\Models\User;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnChannelImportRequestCreated
{
    public function handle(ChannelImportRequestCreated $event)
    {
        $message = $event->message;
        $user = $event->user;

        $admins = User::admins()->get();

        TCNotification::Send($admins, new GeneralNotification(
            Notification::TYPE_NEW_IMPORT_REQUEST,
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
                'youtube_url' => $user->channel->youtube_channel_url
            ],
            [
                'entity_type' => get_class($message),
                'entity_id' => $message->id,
            ]
        ));

        return true;
    }
}
