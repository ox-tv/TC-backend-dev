<?php

namespace App\Console\Commands;

use App\Models\ChannelStatisticsDaily;
use App\Models\PaymentDetails;
use App\Models\Scopes\OrderDescScope;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddVideosCountToChannelStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:channel-statistics:count-published-videos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired pasyment details and change status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $videos = Video::where('status', Video::STATUS_PUBLISHED)->withoutGlobalScope(OrderDescScope::class)->get();

        foreach ($videos as $video){
            $channel = $video->channel;

            $statistics = ChannelStatisticsDaily::firstOrNew([
                'channel_id' => $channel->id,
                'date' => $video->created_at->startOfDay(),
            ]);
            $statistics->published_videos += 1;

            $statistics->save();
        }

        return 0;
    }
}
