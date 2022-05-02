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
        $customFeedTagsText = "Hello there! <br/>
We hope you enjoy Today's Crypto and have had time to click around a bit on the platform. To get the most out of your experience possible, we recommend setting up your custom content feed. You will find this under your profile and \"My Custom Feed.\" There you can add tags for everything you think is relevant to you. You will then see this related content at the top of your home page when you are logged in. You can also choose to get content based on your favorite cryptocurrencies that you have selected under the market page. Stay tight!";

        TCNotification::Send(collect([$user]), new GeneralNotification(
            Notification::TYPE_FILL_CUSTOM_FEED_TAGS,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            ['message' => $customFeedTagsText]
        ));

        return true;
    }
}
