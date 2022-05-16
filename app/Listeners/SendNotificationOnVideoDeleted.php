<?php

namespace App\Listeners;

use App\Events\VideoDeleted;
use App\Events\VideoViewed;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

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

        $video->append(['reason_key', 'reason_text']);

        if (request()->is('api/admin/videos/*')){
            TCNotification::Send(collect([$video->user]), new GeneralNotification(
                Notification::TYPE_DELETE_VIDEO,
                Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
                ['video' => VideoResource::make($video)],
                [
                    'entity_type' => get_class($video),
                    'entity_id' => $video->id,
                ]
            ));
        }

        return true;
    }
}
