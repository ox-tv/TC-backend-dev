<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\PublisherRequestApproved;
use App\Events\Publisher\PublisherRequestRejected;
use App\Events\VideoViewed;
use App\Models\Notification;
use App\Notifications\PublisherApproved;
use App\Notifications\PublisherRejected;
use App\Notifications\TCNotification\TCNotification;

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

        TCNotification::send(collect([$user]), new PublisherRejected(
            Notification::SCOPE_TEXT[Notification::SCOPE_USER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message_id' => $parentMessage->id,
                'reason' => $reason,
            ],
            get_class($user),
            $user->id
        ));

        return true;
    }
}
