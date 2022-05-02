<?php

namespace App\Listeners;

use App\Events\VideoViewed;
use App\Events\VideoWasUnHidden;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\Notifications\UnHideVideo;
use App\TCNotification\GeneralNotification;
use TCNotification;

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

        TCNotification::Send(collect([$video->user]), new GeneralNotification(
            Notification::TYPE_UNHIDE_VIDEO,
            Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
            ['video' => VideoResource::make($video)],
            [
                'entity_type' => get_class($video),
                'entity_id' => $video->id,
            ]
        ));

        return true;
    }
}
