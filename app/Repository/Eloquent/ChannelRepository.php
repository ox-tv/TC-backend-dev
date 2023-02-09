<?php

namespace App\Repository\Eloquent;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ChannelRepository
{
    private $videoRepository;

    public function __construct(VideoRepository $videoRepository)
    {
        $this->videoRepository = $videoRepository;
    }

    public function softDelete($channelId, $options = [])
    {
        try {
            DB::beginTransaction();

            // Remove Videos
            $videos = Video::where('channel_id', $channelId)->get();
            foreach ($videos as $video){
                $this->videoRepository->destroy($video->id, $options);
            }

            // Remove Channel
            Channel::where('id', $channelId)->delete();

            DB::commit();
            return true;

        } catch (Throwable $e) {

            DB::rollback();
            return false;
        }
    }

    public function restore($channelId)
    {
        try {
            DB::beginTransaction();

            Channel::withTrashed()->where('id', $channelId)->restore();

            // Remove Videos
            $videos = Video::withTrashed()->where('channel_id', $channelId)->get();
            foreach ($videos as $video){
                $this->videoRepository->destroy($video->id);
            }

            // Remove Channel

            DB::commit();
            return true;

        } catch (Throwable $e) {

            DB::rollback();
            return false;
        }
    }
}
