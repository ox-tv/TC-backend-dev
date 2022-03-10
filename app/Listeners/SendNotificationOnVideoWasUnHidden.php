<?php

namespace App\Listeners;

use App\Events\VideoViewed;
use App\Events\VideoWasUnHidden;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Models\Notification;
use App\Notifications\TCNotification\TCNotification;
use App\Notifications\UnHideVideo;

class SendNotificationOnVideoWasUnHidden
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoWasUnHidden $event)
    {
        $video = $event->video;

        TCNotification::send(collect([$video->user]), new UnHideVideo(
            Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'video' => videoMinimalItem::make($video),
            ],
            get_class($video),
            $video->id
        ));

        return true;
    }
}
