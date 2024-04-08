<?php

namespace App\Listeners\Video;

use App\Events\VideoViewed;
use App\Models\MonetizePoint;
use App\Models\Video;
use App\Repository\Eloquent\MonetizePointRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class MonetizePointsForVideoViewed
{
    private $monetizePointRepository;

    public function __construct(MonetizePointRepository $monetizePointRepository)
    {
        $this->monetizePointRepository = $monetizePointRepository;
    }

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(VideoViewed $event)
    {
        $user = $event->user;
        $video = $event->video;
        $channel = $video->channel;
        $pointsPerView = config('points.monetize.per_view_video');

        // Check channel is qualified
        if (!$channel->monetization_qualified_at || $channel->monetization_qualified_at > Carbon::now()){
            return 0;
        }

        $this->monetizePointRepository->add([
            'channel_id' => $channel->id,
            'related_to_type' => Video::class,
            'related_to_id' => $video->id,
            'activated_at' => Carbon::now(),
            'type' => MonetizePoint::TYPE_VIDEO_VIEWED,
            'amount' => $pointsPerView,
            'monetization_multiplier' => $channel->monetization_multiplier,
        ], [
            'channel_id',
            'related_to_type',
            'related_to_id',
            'type',
            'date',
        ]);

        // Just log
        $row = MonetizePoint::where('channel_id', $channel->id)
            ->where('related_to_type', Video::class)
            ->where('related_to_id', $video->id)
            ->where('type', MonetizePoint::TYPE_VIDEO_VIEWED)
            ->where('date', Carbon::now()->startOfDay())
            ->first();
        $row && Log::channel('metrics')->warning("MonetizePoint_{$video->id}_view:{$row->original_amount}");
    }
}
