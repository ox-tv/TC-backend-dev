<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use Illuminate\Console\Command;

class ChangePrivateR2ImagesToPublicUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'r2:change-images-to-public-url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload channels old avatar to s3 and put in database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $privateEndPoint = "https://". config('filesystems.disks.r2.account_id') .".r2.cloudflarestorage.com/". config('filesystems.disks.r2.bucket') ."/";
        $publicEndPoint = config('filesystems.disks.r2.public_endpoint');

        // Change video images urls
        $videos = Video::whereNotNull('thumbnail_url')->get();

        foreach ($videos as $video){
            $video->thumbnail_url = str_replace($privateEndPoint, $publicEndPoint, $video->thumbnail_url);
            $video->save();
        }

        // Change channel images url
        $channels = Channel::where(function($q){
            $q->whereNotNull('avatar_url')->orWhereNotNull('cover_url');
        })->get();

        foreach ($channels as $channel){
            $channel->avatar_url = str_replace($privateEndPoint, $publicEndPoint, $channel->avatar_url);
            $channel->cover_url = str_replace($privateEndPoint, $publicEndPoint, $channel->cover_url);
            $channel->save();
        }

        // Change user images url
        $users = User::whereNotNull('avatar_url')->get();

        foreach ($users as $user){
            $user->avatar_url = str_replace($privateEndPoint, $publicEndPoint, $user->avatar_url);
            $user->save();
        }

        // Change images in notifications table
        $notifications = Notification::all();

        foreach ($notifications as $notification){
            $payload = $notification->payload;//dd($payload);

            if (!empty($payload['video']['thumbnail'])){
                $payload['video']['thumbnail'] = explode('?', str_replace($privateEndPoint, $publicEndPoint, $payload['video']['thumbnail']))[0];
            }

            if (!empty($payload['video']['thumbnails'])){
                foreach ($notification->payload['video']['thumbnails'] as $k => $v){
                    $payload['video']['thumbnails'][$k] = explode('?', str_replace($privateEndPoint, $publicEndPoint, $v))[0];
                }
            }

            if (!empty($payload['channel']['cover'])){
                $payload['channel']['cover'] = explode('?', str_replace($privateEndPoint, $publicEndPoint, $payload['channel']['cover']))[0];
            }

            if (!empty($payload['channel']['cover_thumbnails'])){
                foreach ($notification->payload['channel']['cover_thumbnails'] as $k => $v){
                    $payload['channel']['cover_thumbnails'][$k] = explode('?', str_replace($privateEndPoint, $publicEndPoint, $v))[0];
                }
            }

            if (!empty($payload['channel']['avatar'])){
                $payload['channel']['avatar'] = explode('?', str_replace($privateEndPoint, $publicEndPoint, $payload['channel']['avatar']))[0];
            }

            if (!empty($payload['channel']['avatar_thumbnails'])){
                foreach ($notification->payload['channel']['avatar_thumbnails'] as $k => $v){
                    $payload['channel']['avatar_thumbnails'][$k] = explode('?', str_replace($privateEndPoint, $publicEndPoint, $v))[0];
                }
            }

            if (!empty($payload['user']['avatar'])){
                $payload['user']['avatar'] = explode('?', str_replace($privateEndPoint, $publicEndPoint, $payload['user']['avatar']))[0];
            }

            if (!empty($payload['user']['avatar_thumbnails'])){
                foreach ($notification->payload['user']['avatar_thumbnails'] as $k => $v){
                    $payload['user']['avatar_thumbnails'][$k] = explode('?', str_replace($privateEndPoint, $publicEndPoint, $v))[0];
                }
            }

            $notification->payload = $payload;
            $notification->save();
        }

        return 0;
    }
}
