<?php

namespace App\Listeners\Channels;

use App\Events\Channels\ChannelImportRequestCreated;
use App\Events\VideoViewed;
use App\Http\Resources\Message\MessageItem;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\NewImportRequest;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnChannelImportRequestCreated
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(ChannelImportRequestCreated $event)
    {
        $message = $event->message;
        $user = $event->user;

        $admins = User::admins()->get();

        TCNotification::send($admins, new NewImportRequest(
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
                'youtube_url' => $user->channel->youtube_channel_url
            ],
            get_class($message),
            $message->id
        ));

        return true;
    }
}
