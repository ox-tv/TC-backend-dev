<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Events\VideoViewed;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

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

        TCNotification::Send(collect([$user]), new GeneralNotification(
            Notification::TYPE_FILL_CUSTOM_FEED_TAGS,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            ['message' => $customFeedTagsText]
        ));

        return true;
    }
}
