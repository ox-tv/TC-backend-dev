<?php

namespace App\Listeners\Publisher;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\Channels\ChannelImportRequestCreated;
use App\Events\Publisher\PublisherRequestApproved;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Message\MessageItem;
use App\Mail\ImportRequestCompletedMail;
use App\Mail\PublisherApprovedMail;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\ImportRequestAccepted;
use App\Notifications\ImportRequestCompleted;
use App\Notifications\NewImportRequest;
use App\Notifications\TCNotification\TCNotification;
use Illuminate\Support\Facades\Mail;

class SendEmailOnPublisherRequestApproved
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

        Mail::to($user->email)
            ->queue(new PublisherApprovedMail());

        return true;
    }
}
