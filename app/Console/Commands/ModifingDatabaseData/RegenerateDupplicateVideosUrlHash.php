<?php

namespace App\Console\Commands\ModifingDatabaseData;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegenerateDupplicateVideosUrlHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:video:regenerate_url_hash';

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
        $videos = Video::withTrashed()->whereRaw(DB::raw('url_hash in (SELECT `url_hash` FROM (SELECT count(*) as `count`, `url_hash` FROM `videos` WHERE 1 GROUP BY `url_hash`) T1 where T1.count > 1)'))->get();

        foreach ($videos as $video){
            if (!in_array($video->channel_id, ['20227', '20228', '20226'])){
                continue;
            }

            do{
                $urlHash = Str::random(12);
            }while(Video::where('url_hash', $urlHash)->exists());

            $video->url_hash = $urlHash;
            $video->save();
        }

        return 0;
    }
}
