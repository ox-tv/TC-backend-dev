<?php

namespace App\Console\Commands\JustReporting;

use App\Models\Channel2StatisticsDaily;
use App\Models\ChannelStatisticsDaily;
use App\Models\PaymentDetails;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TrendingVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:reporting:trending-videos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $videoIds =  Video::typeVideo()->published()->pluck('id')->toArray();

        $trendingVideos = Channel2StatisticsDaily::raw(function($collection) use ($videoIds) {
            return $collection->aggregate([
                ['$match' => [
                    'date' => ['$gte'=> Channel2StatisticsDaily::fromDateTime(Carbon::now()->subDays(3))],
                    'video_id' => ['$in'=> $videoIds],
                ]],
                ['$group' => [
                    '_id' => '$video_id',
                    'views_amount' => ['$sum' => '$views_total'],
                    'likes_amount' => ['$sum' => ['$multiply' => [['$subtract' => ['$likes_total', '$dislikes_total']], 50]]],
                    'amount' => [
                        '$sum' => [
                            '$add' => [
                                '$views_total',
                                ['$multiply' => [['$subtract' => ['$likes_total', '$dislikes_total']], 50]]
                            ]
                        ]
                    ],
                ]],
                ['$sort' => ['amount' => -1, '_id' => -1]],
                ['$limit' => 24]
            ]);
        })->toArray();

        dd($trendingVideos);

        return 0;
    }
}
