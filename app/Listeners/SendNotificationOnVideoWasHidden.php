<?php

namespace App\Listeners;

use App\Events\VideoCreated;
use App\Events\VideoDeleted;
use App\Events\VideoViewed;
use App\Events\VideoWasHidden;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Models\ChannelStatisticsDaily;
use App\Models\Notification;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use App\Notifications\DeleteVideo;
use App\Notifications\HideVideo;
use App\Notifications\NewVideoPublished;
use App\Notifications\TCNotification\TCNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
                'video' => videoMinimalItem::make($video),
            ],
            get_class($video),
            $video->id
        ));

        return true;
    }
}
