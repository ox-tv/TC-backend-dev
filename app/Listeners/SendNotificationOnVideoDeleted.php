<?php

namespace App\Listeners;

use App\Events\VideoDeleted;
use App\Events\VideoViewed;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\Notifications\DeleteVideo;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnVideoDeleted
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoDeleted $event)
    {
        $video = $event->video;

        if (request()->is('api/admin/videos/*')){
            TCNotification::send(collect([$video->user]), new DeleteVideo(
                Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
                Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                [
                    'video' => VideoResource::make($video),
                ],
                get_class($video),
                $video->id
            ));
        }

        return true;
    }
}
