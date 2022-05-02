<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\PublisherRequestApproved;
use App\Events\VideoViewed;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnPublisherRequestApproved
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(PublisherRequestApproved $event)
    {
        $user = $event->user;

        TCNotification::Send(collect([$user]), new GeneralNotification(
            Notification::TYPE_PUBLISHER_APPROVED,
            Notification::SCOPE_TEXT[Notification::SCOPE_USER],
            [],
            [
                'entity_type' => get_class($user),
                'entity_id' => $user->id,
            ]
        ));

        return true;
    }
}
