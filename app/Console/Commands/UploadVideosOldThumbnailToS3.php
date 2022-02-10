<?php

namespace App\Console\Commands;

use App\Libraries\CoinMarketCapClient;
use App\Models\CryptoCurrency;
use App\Models\User;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class UploadVideosOldThumbnailToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:videos-old-thumbnail-to-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload videos old thumbnail to s3 and put in database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $videos = Video::withTrashed()->whereNotNull('thumbnail')->get();
        $imageManager = new ImageManager();
        $s3 = Storage::disk('s3');
        $directory = 'files';
        $sizes = config('upload.thumbnail_sizes');

        foreach ($videos as $video){
            try{
                $file = Storage::disk('public')->path($video->thumbnail);
                $originalFilePath = $s3->putFile($directory, $file, 'public');
                $url = $s3->url($originalFilePath);

                // Create multiple sizes
                $originalImage = $imageManager->make($url);
                $fileName = pathinfo($url, PATHINFO_BASENAME);

                foreach ($sizes as $size){
                    $image = clone $originalImage;
                    $key = ($size['w']?:'auto') . '_' . ($size['h']?:'auto');
                    $filePath = $directory . "/{$key}/" . $fileName;

                    $image->resize($size['w'], $size['h'], function ($constraint) use ($size) {
                        if (empty($size['w']) || empty($size['h'])){
                            $constraint->aspectRatio();
                        }
                    });

                    $s3->put($filePath, $image->stream(), 'public');
                }

                $video->thumbnail_url = $url;
                $video->thumbnail = null;
                $video->save();

            }catch (Exception $e){
                dd($e->getMessage());
                Log::error($e->getMessage());
            }
        }

        return 0;
    }
}
