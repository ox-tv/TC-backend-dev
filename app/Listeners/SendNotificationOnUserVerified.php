<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Mail\WelcomeToReferralUserMail;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use Illuminate\Support\Facades\Mail;
use TCNotification;

class SendNotificationOnUserVerified
{

    public function handle(UserVerified $event)
    {
        $user = $event->user;

        // CustomFeed Notification
        $customFeedTagsText = "Improve your experience by setting up your custom feed. By doing so, you will create a more relevant content feed based on your favorite cryptos. Set up your custom feed now.";

        TCNotification::Send(collect([$user]), new GeneralNotification(
            Notification::TYPE_FILL_CUSTOM_FEED_TAGS,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            ['message' => $customFeedTagsText, 'subject' => 'Hey!']
        ));


        // Send Mail and Notification to new user
        Mail::to($user->email)
            ->queue(new WelcomeToReferralUserMail());

        $welcomeText = "Set up your custom content feed by clicking the “Customize” button next to your “Videos for you” section on the home page for the best possible experience. You will also earn your first 25 TCG tokens for doing so! Happy streaming.";

        TCNotification::Send(collect([$user]), new GeneralNotification(
            Notification::TYPE_WELCOME_TO_REFERRAL_USERS,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            ['message' => $welcomeText, 'subject' => "Congratulations, and welcome to Today’s Crypto!"]
        ));

        return true;
    }
}
