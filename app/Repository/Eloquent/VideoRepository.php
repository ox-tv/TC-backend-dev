<?php

namespace App\Repository\Eloquent;

use App\Models\UserVideo;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VideoRepository
{
    private $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    public function destroy($videoId, $options = [])
    {
        DB::transaction(function () use ($videoId, $options) {

            $reasonKey = $options['reason_key'] ?? null;
            $reasonText = $options['reason_text'] ?? null;

            if ($reasonKey && $reasonText){
                Video::where('id', $videoId)
                    ->update([
                        'reason_key' => $reasonKey,
                        'reason_text' => $reasonText,
                    ]);
            }

            // Remove comments
            $this->commentRepository->destroyByVideoId($videoId);

            // Remove UserVideo bookmarks
            UserVideo::where('video_id', $videoId)
                ->where('relation', UserVideo::BOOKMARKED_RELATION)
                ->delete();

            Video::where('id', $videoId)->delete();
        });

        return true;
    }

    public function update($videoId, $data = [])
    {
        $allowedKeys = [
            'media_type',
            'status',
            'url_hash',
            'title',
            'slug',
            'file_url',
            'thumbnail_url',
            'user_id',
            'category_id',
            'language_id',
            'channel_id',
            'description',
            'published_at',
            'duration',
            'view_count',
            'watch_time',
            'reason_key',
            'reason_text',
        ];
        $updateData = array_filter($data, function($v, $k) use ($allowedKeys) {
            return in_array($k, $allowedKeys);
        }, ARRAY_FILTER_USE_BOTH);

        return Video::where('id', $videoId)->update($updateData);
    }
}
