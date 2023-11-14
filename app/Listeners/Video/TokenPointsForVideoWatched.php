<?php

namespace App\Listeners\Video;

use App\Events\VideoWatched;
use App\Models\TokenPoint;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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

        /*$duration = DB::table('watch_times')
                ->whereDate('created_at', Carbon::today())
                ->where('user_id', $user->id)
                ->selectRaw("SUM(end_time - start_time) as duration")
                ->first()->duration?? 0;*/

        $watchTimes = DB::table('watch_times')
            ->whereDate('created_at', Carbon::today())
            ->where('user_id', $user->id)
            ->select(["end_time", "start_time"])->get();

        $totalTimes = [];
        foreach ($watchTimes as $watchTime){
            $totalTimes[] = $watchTime->end_time - $watchTime->start_time;
        }

        $watchTimeDuration = array_sum($totalTimes);

        $durationInMinute = intval($watchTimeDuration / 60);

        $maxMinutesToEarn = 1440; //$user->is_hero? 180 : 1440;
        $durationInMinute = min($durationInMinute, $maxMinutesToEarn);


        $type = $user->is_hero? TokenPoint::TYPE_WATCH_A_VIDEO_AS_HERO : TokenPoint::TYPE_WATCH_A_VIDEO;
        $row = Cache::remember("tokenpoint_user{$user->id}_type{$type}_current", Carbon::now()->endOfDay() , function () use ($user, $type){
            return TokenPoint::where('date', Carbon::now()->startOfDay())
                ->where('user_id', $user->id)
                ->where('type', $type)
                ->first();
        });

        if ($row){
            $row->amount = $user->is_hero? $durationInMinute * 2 : $durationInMinute;
            $row->save();
        }else{
            $row = $this->tokenPointRepository->add([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $user->is_hero? $durationInMinute * 2 : $durationInMinute,
            ]);
        }

        Cache::put("tokenpoint_user{$user->id}_type{$type}_current", $row, Carbon::now()->endOfDay());

        return true;
    }
}
