<?php

namespace App\Listeners\Video;

use App\Events\VideoUpdated;
use App\Events\VideoViewed;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\Models\TokenPoint;
use App\Models\Video;
use App\Repository\Eloquent\TokenPointRepository;
use App\TCNotification\GeneralNotification;
use TCNotification;

class TokenPointsForVideoUpdated
{
    private $tokenPointRepository;

    public function __construct(TokenPointRepository $tokenPointRepository)
    {
        $this->tokenPointRepository = $tokenPointRepository;
    }

    public function handle(VideoUpdated $event)
    {
        $oldVideo = $event->oldVideo;
        $video = $event->video;
        $pointAmount = config('points.token.publish_a_media');

        if (
            $video->status == Video::STATUS_PUBLISHED
            && $oldVideo->status != Video::STATUS_PUBLISHED
            && is_null($oldVideo->published_at)
            && $video->upload_method != Video::UPLOAD_METHOD_YOUTUBE_AUTO_IMPORT
        ){
            $this->tokenPointRepository->add([
                'user_id' => $video->user_id,
                'type' => TokenPoint::TYPE_PUBLISH_A_MEDIA,
                'amount' => $pointAmount,
            ]);
        }

        return true;
    }
}
