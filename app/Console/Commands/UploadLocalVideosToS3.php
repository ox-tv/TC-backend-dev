<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadLocalVideosToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:local-videos-to-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload local videos to s3 and put in database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $videos = Video::withTrashed()->whereNotNull('file_path')->get();
        $s3 = Storage::disk('s3');

        foreach ($videos as $video){
            try{
                $channel = $video->channel()->withTrashed()->first();
                $directory = "channel/{$channel->id}/videos";
                $file = Storage::disk('videos')->path($video->file_path);

                if (file_exists($file)) {
                    $originalFilePath = $s3->putFile($directory, $file, 'public');
                    $url = $s3->url($originalFilePath);
                    $video->file_url = $url;
                    $video->file_path = null;
                    $video->save();
                }


            }catch (Exception $e){
                dump($e->getMessage());
                Log::error($e->getMessage());
                continue;
            }
        }

        return 0;
    }
}
