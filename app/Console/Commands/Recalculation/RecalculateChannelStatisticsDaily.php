<?php

namespace App\Console\Commands\Recalculation;

use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use App\Models\ChannelUser;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserVideo;
use App\Models\Video;
use App\Models\WatchTime;
use App\Models\WatchTimeMongo;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateChannelStatisticsDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:recalc:channel-statistics-daily  {--dateFrom=}  {--dateTo=}';

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
        $dateFrom = Carbon::parse($this->option('dateFrom'));
        $dateTo = Carbon::parse($this->option('dateTo'));

        if (
            $dateFrom->format('Y-m-d') != $this->option('dateFrom')
            || $dateTo->format('Y-m-d') != $this->option('dateTo')
        ){
            dd('Invalid dateFrom or dateTo option');
        }

        Channel2StatisticsDaily::whereBetween('date', [$dateFrom, $dateTo])->delete();

        $periods = CarbonPeriod::create($dateFrom, '1 day', $dateTo);
        foreach ($periods as $day) {
            $this->recalculateSubscribers($day);
            $this->recalculateUploadVideosTotal($day);
            $this->recalculateComments($day);
            $this->recalculateLikes($day);
            $this->recalculateDisLikes($day);
            $this->recalculateWatchTimesAndViews($day);
        }

        return 0;
    }


    private function recalculateWatchTimesAndViews($day)
    {
        $rows = WatchTimeMongo::whereDate('created_at', $day)->get();
        $viewsHistory = [];
        $watchTimeHistory = [];

        foreach ($rows as $row){
            $video = Video::find($row->video_id);
            if (!$video){continue;}
            $user = User::find($row->user_id);
            if (!$user){continue;}

            $wasHero = $user->hero_due_at >= $day;
            $duration = $row->end_time - $row->start_time;

            $temp = $watchTimeHistory["{$video->channel_id}_{$video->id}"][(int) $wasHero]?? 0;
            $watchTimeHistory["{$video->channel_id}_{$video->id}"][(int) $wasHero] = $temp + $duration;

            $viewsHistory["{$video->channel_id}_{$video->id}"][$user->id] = $wasHero;

//            $statistics = Channel2StatisticsDaily::firstOrNew([
//                'video_id' => $video->id,
//                'channel_id' => $video->channel_id,
//                'date' => $day,
//            ]);
//
//            $statistics->watch_time_total += $duration;
//
//            if($wasHero){
//                $statistics->watch_time_hero += $duration;
//            }else{
//                $statistics->watch_time_non_hero += $duration;
//            }
//
//            $statistics->save();
        }

        foreach ($watchTimeHistory as $key => $value){
            [$channelId, $videoId] = explode('_', $key);
            $statistics = Channel2StatisticsDaily::firstOrNew([
                'video_id' => (int)$videoId,
                'channel_id' => (int)$channelId,
                'date' => $day,
            ]);
            $statistics->watch_time_total = ($value[0]??0) + ($value[1]??0);
            $statistics->watch_time_hero = $value[1]??0;
            $statistics->watch_time_non_hero = $value[0]??0;
            $statistics->save();
        }

        foreach ($viewsHistory as $key => $value){
            [$channelId, $videoId] = explode('_', $key);
            $statistics = Channel2StatisticsDaily::firstOrNew([
                'video_id' => (int)$videoId,
                'channel_id' => (int)$channelId,
                'date' => $day,
            ]);
            foreach ($value as $userId => $wasHero){
                $statistics->views_total += 1;
                if ($wasHero){
                    $statistics->views_hero += 1;
                }else{
                    $statistics->views_non_hero += 1;
                }
            }

            $statistics->save();
        }
    }

    private function recalculateDisLikes($day)
    {
        $rows = UserVideo::where('relation', UserVideo::DISLIKED_RELATION)
            ->whereDate('created_at', $day)->get();

        foreach ($rows as $row){
            $video = Video::find($row->video_id);
            if (!$video){continue;}
            $user = User::find($row->user_id);
            if (!$user){continue;}

            $wasHero = $user->hero_due_at >= $day;

            $statistics = Channel2StatisticsDaily::firstOrNew([
                'video_id' => $video->id,
                'channel_id' => $video->channel_id,
                'date' => $day,
            ]);

            $statistics->dislikes_total += 1;

            if($wasHero){
                $statistics->dislikes_hero += 1;
            }else{
                $statistics->dislikes_non_hero += 1;
            }

            $statistics->save();
        }
    }

    private function recalculateLikes($day)
    {
        $rows = UserVideo::where('relation', UserVideo::LIKED_RELATION)
            ->whereDate('created_at', $day)->get();

        foreach ($rows as $row){
            $video = Video::find($row->video_id);
            if (!$video){continue;}
            $user = User::find($row->user_id);
            if (!$user){continue;}

            $wasHero = $user->hero_due_at >= $day;

            $statistics = Channel2StatisticsDaily::firstOrNew([
                'video_id' => $video->id,
                'channel_id' => $video->channel_id,
                'date' => $day,
            ]);

            $statistics->likes_total += 1;

            if($wasHero){
                $statistics->likes_hero += 1;
            }else{
                $statistics->likes_non_hero += 1;
            }

            $statistics->save();
        }
    }

    private function recalculateComments($day)
    {
        $rows = Comment::whereDate('created_at', $day)->get();

        foreach ($rows as $row){
            $video = $row->video()->first();
            $user = User::find($row->user_id);
            if (!$user){continue;}

            $wasHero = $user->hero_due_at >= $day;

            $statistics = Channel2StatisticsDaily::firstOrNew([
                'video_id' => $video->id,
                'channel_id' => $video->channel_id,
                'date' => $day,
            ]);

            $statistics->comments_total += 1;

            if($wasHero){
                $statistics->comments_hero += 1;
            }else{
                $statistics->comments_non_hero += 1;
            }

            $statistics->save();
        }
    }

    private function recalculateUploadVideosTotal($day)
    {
        $rows = Video::whereDate('created_at', $day)->get();

        foreach ($rows as $row){

            $statistics = Channel2StatisticsDaily::firstOrNew([
                'channel_id' => $row->channel_id,
                'video_id' => null,
                'date' => $day,
            ]);

            $statistics->upload_videos_total += 1;

            if ($row->status == Video::STATUS_PUBLISHED){
                $statistics->published_videos += 1;
            }

            $statistics->save();
        }
    }

    private function recalculateSubscribers($day)
    {
        $rows = ChannelUser::whereDate('created_at', $day)->get();

        foreach ($rows as $row){

            $user = User::find($row->user_id);
            if (!$user){continue;}

            $wasHero = $user->hero_due_at >= $day;

            $statistics = Channel2StatisticsDaily::firstOrNew([
                'channel_id' => $row->channel_id,
                'video_id' => null,
                'date' => $day,
            ]);

            $statistics->subscribers_total += 1;

            if($wasHero){
                $statistics->subscribers_hero += 1;
            }else{
                $statistics->subscribers_non_hero += 1;
            }

            $statistics->save();
        }
    }

    public function test()
    {
        //disable ONLY_FULL_GROUP_BY
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

        $channels = Channel::where('id', 1)->get();

        foreach ($channels as $channel){

            $totalVideos = Video::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") date, count(*) AS videos_count')
                ->where('channel_id', $channel->id)
                ->groupby('date')
                ->orderBy('date', 'ASC')
                ->pluck('vides_count', 'date')->toArray();

            foreach ($totalVideos as $date => $count){

                $statistics = Channel2StatisticsDaily::firstOrNew([
                    'channel_id' => $channel->id,
                    'video_id' => null,
                    'date' => Carbon::parse($date)->staOfDay(),
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

                $statistics = Channel2StatisticsDaily::firstOrNew([
                    'channel_id' => $channel->id,
                    'video_id' => null,
                    'date' => Carbon::parse($date)->startOfDay(),
                ]);

                $statistics->published_videos = $count;
                $statistics->save();
            }
        }

        //re-enable ONLY_FULL_GROUP_BY
        DB::statement("SET sql_mode=(SELECT CONCAT(@@sql_mode, ',ONLY_FULL_GROUP_BY'));");
    }
}
