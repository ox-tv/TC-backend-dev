<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Events\VideoViewed;
use App\Mail\ImportRequestCompletedMail;
use App\Mail\WelcomeToReferralUserMail;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use Illuminate\Support\Facades\Mail;
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

        // CustomFeed Notification
        $customFeedTagsText = "Hey!
        Improve your experience by setting up your custom feed. By doing so, you will create a more relevant content feed based on your favorite cryptos. Set up your custom feed now.";

        TCNotification::Send(collect([$user]), new GeneralNotification(
            Notification::TYPE_FILL_CUSTOM_FEED_TAGS,
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            ['message' => $customFeedTagsText]
        ));


        // Send Mail and Notification to referral user
        if ($user->referrer_id){
            Mail::to($user->email)
                ->queue(new WelcomeToReferralUserMail());

            $welcomeText = "Congratulation, and welcome to Today’s Crypto! 
<br/>
You are gifted two months of free Hero Membership, meaning you can enjoy Today’s Crypto utterly free from Ads and unlimited tracking of coins/tokens. Set up your custom content feed by clicking the “Customize” button next to your “Videos for you” section on the home page for the best possible experience.
<br/>
Best wishes from the team at Today’s Crypto";

            TCNotification::Send(collect([$user]), new GeneralNotification(
                Notification::TYPE_WELCOME_TO_REFERRAL_USERS,
                Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
                ['message' => $welcomeText, 'subject' => "Congratulation, and welcome to Today’s Crypto!"]
            ));
        }

        return true;
    }
}
