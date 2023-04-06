<?php

namespace App\Listeners\Video;

use App\Events\VideoCreated;
use App\Events\VideoViewed;
use App\Models\Channel2StatisticsDaily;
use App\Models\TokenPoint;
use App\Models\Video;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;

class TokenPointsForVideoCreated
{
    private $tokenPointRepository;

    public function __construct(TokenPointRepository $tokenPointRepository)
    {
        $this->tokenPointRepository = $tokenPointRepository;
    }

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoCreated $event)
    {
        $video = $event->video;
        $pointAmount = config('points.token.publish_a_media');

        if (
            $video->status == Video::STATUS_PUBLISHED
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
