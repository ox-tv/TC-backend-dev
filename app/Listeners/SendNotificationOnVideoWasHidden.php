<?php

namespace App\Listeners;

use App\Events\VideoWasHidden;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnVideoWasHidden
{
    public function handle(VideoWasHidden $event)
    {
        $video = $event->video;

        $video->append(['reason_key', 'reason_text']);

        TCNotification::Send(collect([$video->user]), new GeneralNotification(
            Notification::TYPE_HIDE_VIDEO,
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
