<?php

namespace App\Repository\Eloquent;

use App\Models\UserVideo;
use App\Models\Video;
use Illuminate\Support\Facades\DB;

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
}