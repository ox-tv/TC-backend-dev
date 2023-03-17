<?php

namespace App\Listeners\Video;

use App\Events\VideoWatched;
use App\Models\TokenPoint;
use App\Repository\Eloquent\TokenPointRepository;
use Illuminate\Support\Facades\DB;

class TokenPointsForVideoWatched
{
    private $tokenPointRepository;

    public function __construct(TokenPointRepository $tokenPointRepository)
    {
        $this->tokenPointRepository = $tokenPointRepository;
    }

    public function handle(VideoWatched $event)
    {
        $user = $event->user;
        $video = $event->video;
        $startTime = $event->startTime;
        $endTime = $event->endTime;
        $videoDuration = $video->duration;

        $watchTimeDuration = DB::table('watch_times')
                ->where('video_id', $video->id)
                ->where('user_id', $user->id)
                ->selectRaw("SUM(end_time - start_time) as duration")
                ->first()->duration?? 0;

        $beforePercent = ($watchTimeDuration - ($endTime - $startTime)) * 100 / $videoDuration;
        $afterPercent = $watchTimeDuration * 100 / $videoDuration;

        if (!($beforePercent < 50 && $afterPercent >= 50)){
            return true;
        }

        $this->tokenPointRepository->add([
            'user_id' => $user->id,
            'type' => $user->is_hero? TokenPoint::TYPE_WATCH_A_VIDEO_AS_HERO : TokenPoint::TYPE_WATCH_A_VIDEO,
            'amount' => $user->is_hero? config('points.token.watch_a_video_as_hero') : config('points.token.watch_a_video'),
        ]);

        return true;
    }
}
