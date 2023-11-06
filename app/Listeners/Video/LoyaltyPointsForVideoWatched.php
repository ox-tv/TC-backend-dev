<?php

namespace App\Listeners\Video;

use App\Events\VideoWatched;
use App\Models\LoyaltyPoint;
use App\Repository\Eloquent\LoyaltyPointRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoyaltyPointsForVideoWatched
{
    private $loyaltyPointRepository;

    public function __construct(LoyaltyPointRepository $loyaltyPointRepository)
    {
        $this->loyaltyPointRepository = $loyaltyPointRepository;
    }

    public function handle(VideoWatched $event)
    {
        $user = $event->user;
        $video = $event->video;
        $startTime = $event->startTime;
        $endTime = $event->endTime;
        $videoDuration = $video->duration;

        /*
        $watchTimeDuration = DB::table('watch_times')
                ->where('video_id', $video->id)
                ->where('user_id', $user->id)
                ->selectRaw("SUM(end_time - start_time) as duration")
                ->first()->duration?? 0;
        */

        $watchTimes = DB::table('watch_times')->where('video_id', $video->id)->where('user_id', $user->id)->select(["end_time", "start_time"])->get();

        $totalTimes = [];
        foreach ($watchTimes as $watchTime){
            $totalTimes[] = $watchTime->end_time - $watchTime->start_time;
        }

        $watchTimeDuration = array_sum($totalTimes);

        $beforePercent = ($watchTimeDuration - ($endTime - $startTime)) * 100 / $videoDuration;
        $afterPercent = $watchTimeDuration * 100 / $videoDuration;

        if (!($beforePercent < 50 && $afterPercent >= 50)){
            return;
        }

        if ($user->is_hero){
            $pointsPerWatched = config('points.loyalty.per_watch_video_as_hero');
        }else{
            $pointsPerWatched = config('points.loyalty.per_watch_video_as_non_hero');
        }

        $this->loyaltyPointRepository->add([
            'user_id' => $user->id,
            'activated_at' => Carbon::now(),
            'type' => LoyaltyPoint::TYPE_VIDEO_WATCHED,
            'amount' => $pointsPerWatched,
        ]);

        return 0;
    }
}
