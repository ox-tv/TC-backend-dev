<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\PublisherRequestRejected;
use App\Events\VideoViewed;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnPublisherRequestRejected
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(PublisherRequestRejected $event)
    {
        $user = $event->user;
        $reason = $event->reason;
        $parentMessage = $event->parentMessage;

        TCNotification::Send(collect([$user]), new GeneralNotification(
            Notification::TYPE_PUBLISHER_REJECTED,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            [
                'message_id' => $parentMessage->id,
                'reason' => $reason,
            ],
            [
                'entity_type' => get_class($user),
                'entity_id' => $user->id,
            ]
        ));

        return true;
    }
}
