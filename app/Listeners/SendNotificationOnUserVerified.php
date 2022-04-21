<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Events\VideoViewed;
use App\Models\Notification;
use App\Notifications\FillCustomFeedTags;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnUserVerified
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(UserVerified $event)
    {
        $user = $event->user;
        $customFeedTagsText = "Hey!
        Improve your experience by setting up your custom feed. By doing so, you will create a more relevant content feed based on your favorite cryptos. Set up your custom feed now.";

        TCNotification::send(collect([$user]), new FillCustomFeedTags(
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            ['message' => $customFeedTagsText]
        ));

        return true;
    }
}
