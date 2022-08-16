<?php

namespace App\Repository\Eloquent;

use App\Models\Comment;
use App\Models\CommentUser;
use Illuminate\Support\Facades\DB;

class CommentRepository
{
    public function destroy($commentId, $options = [])
    {
        DB::transaction(function () use ($commentId, $options) {

            $reasonKey = $options['reason_key'] ?? null;
            $reasonText = $options['reason_text'] ?? null;

            if ($reasonKey && $reasonText){
                Comment::where('id', $commentId)
                    ->update([
                        'reason_key' => $reasonKey,
                        'reason_text' => $reasonText,
                    ]);
            }

            // Remove Replies
            Comment::where('parent_id', $commentId)->delete();

            // Remove CommentUser mention and remembers for comment and replies
            CommentUser::whereExists(function ($query) use ($commentId) {
                    $query->select(DB::raw(1))
                        ->from('comments')
                        ->whereColumn('comment_user.comment_id', 'comments.id')
                        ->where(function ($q) use ($commentId){
                            $q->where('id', $commentId)->orWhere('parent_id', $commentId);
                        });
                })
                ->whereIn('relation', [CommentUser::REMEMBERED_RELATION, CommentUser::MENTION_RELATION])
                ->delete();

            Comment::where('id', $commentId)->delete();
        });

        return true;
    }

    public function destroyByVideoId($videoId, $options = [])
    {
        DB::transaction(function () use ($videoId, $options) {

            $reasonKey = $options['reason_key'] ?? null;
            $reasonText = $options['reason_text'] ?? null;

            if ($reasonKey && $reasonText){
                Comment::where('video_id', $videoId)
                    ->update([
                        'reason_key' => $reasonKey,
                        'reason_text' => $reasonText,
                    ]);
            }

            // Remove CommentUser mention and remembers By videoId for comment and replies
            CommentUser::whereExists(function ($query) use ($videoId) {
                    $query->select(DB::raw(1))
                        ->from('comments')
                        ->whereColumn('comment_user.comment_id', 'comments.id')
                        ->where('video_id', $videoId);
                })
                ->whereIn('relation', [CommentUser::REMEMBERED_RELATION, CommentUser::MENTION_RELATION])
                ->delete();

            // Remove Comments and Replies together (using video id)
            Comment::where('video_id', $videoId)->delete();
        });

        return true;
    }
}