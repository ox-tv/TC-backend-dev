<?php

namespace App\Console\Commands\Recalculation;

use App\Models\Channel;
use App\Models\ChannelStatisticsDaily;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateChannelStatisticsDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:recalc:channel-statistics-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate video statistics daily from scratch';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //disable ONLY_FULL_GROUP_BY
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

        $channels = Channel::where('id', 1)->get();

        foreach ($channels as $channel){

            $totalVideos = Video::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") date, count(*) AS vides_count')
                ->where('channel_id', $channel->id)
                ->groupby('date')
                ->orderBy('date', 'ASC')
                ->pluck('vides_count', 'date')->toArray();

            foreach ($totalVideos as $date => $count){
                $statistics = ChannelStatisticsDaily::firstOrNew([
                    'channel_id' => $channel->id,
                    'date' => Carbon::parse($date)->startOfDay(),
                ]);

                $statistics->upload_videos_total = $count;
                $statistics->save();
            }

            $totalPublishedVideos = Video::selectRaw('DATE_FORMAT(IF(published_at AND published_at > created_at, published_at, created_at), "%Y-%m-%d") date, count(*) AS vides_count')
                ->where('channel_id', $channel->id)
                ->where('status', Video::STATUS_PUBLISHED)
                ->groupby('date')
                ->orderBy('date', 'ASC')
                ->pluck('vides_count', 'date')->toArray();

            foreach ($totalPublishedVideos as $date => $count){
                $statistics = ChannelStatisticsDaily::firstOrNew([
                    'channel_id' => $channel->id,
                    'date' => Carbon::parse($date)->startOfDay(),
                ]);

                $statistics->published_videos = $count;
                $statistics->save();
            }
        }

        //re-enable ONLY_FULL_GROUP_BY
        DB::statement("SET sql_mode=(SELECT CONCAT(@@sql_mode, ',ONLY_FULL_GROUP_BY'));");

        return 0;
    }
}
