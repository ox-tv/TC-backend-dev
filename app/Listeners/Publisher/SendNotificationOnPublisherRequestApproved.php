<?php

namespace App\Listeners\Publisher;

use App\Events\Publisher\PublisherRequestApproved;
use App\Events\VideoViewed;
use App\Models\Notification;
use App\Notifications\PublisherApproved;
use App\Notifications\TCNotification\TCNotification;

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

        TCNotification::send(collect([$user]), new PublisherApproved(
            Notification::SCOPE_TEXT[Notification::SCOPE_USER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [],
            get_class($user),
            $user->id
        ));

        return true;
    }
}
