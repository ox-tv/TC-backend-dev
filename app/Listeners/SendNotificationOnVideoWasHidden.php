<?php

namespace App\Listeners;

use App\Events\VideoViewed;
use App\Events\VideoWasHidden;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\Notifications\HideVideo;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnVideoWasHidden
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoWasHidden $event)
    {
        $video = $event->video;

        TCNotification::send(collect([$video->user]), new HideVideo(
            Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'video' => VideoResource::make($video),
            ],
            get_class($video),
            $video->id
        ));

        return true;
    }
}
