<?php

namespace App\Console\Commands;

use App\Libraries\CoinMarketCapClient;
use App\Models\CryptoCurrency;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class UploadUsersOldAvatarToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:users-old-avatar-to-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload users old avatar to s3 and put in database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::withTrashed()->whereNotNull('avatar')->get();
        $imageManager = new ImageManager();
        $s3 = Storage::disk('s3');
        $directory = 'files';
        $sizes = config('upload.thumbnail_sizes');

        foreach ($users as $user){
            try{
                $file = Storage::disk('public')->path($user->avatar);
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

                $user->avatar_url = $url;
                $user->avatar = null;
                $user->save();

            }catch (Exception $e){
                Log::error($e->getMessage());
            }
        }

        return 0;
    }
}
