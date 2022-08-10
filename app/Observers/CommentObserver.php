<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\CommentUser;

class CommentObserver
{
    public function saving(Comment $Comment)
    {

    }

    /**
     * Handle the Comment "created" event.
     *
     * @param  \App\Models\Comment  $Comment
     * @return void
     */
    public function created(Comment $Comment)
    {
        //
    }

    /**
     * Handle the Comment "updated" event.
     *
     * @param  \App\Models\Comment  $Comment
     * @return void
     */
    public function updated(Comment $Comment)
    {
        //
    }

    /**
     * Handle the Comment "deleted" event.
     *
     * @param  \App\Models\Comment  $Comment
     * @return void
     */
    public function deleted(Comment $Comment)
    {
        // Remove remembers and mentions of current comment and replies from comment_user table
        $repliesId = $Comment->replies()->pluck('id')->toArray();
        CommentUser::whereIn('comment_id', array_merge([$Comment->id], $repliesId))
            ->whereIn('relation', [CommentUser::REMEMBERED_RELATION, CommentUser::MENTION_RELATION])
            ->delete();

        // Remove comment replies
        $Comment->replies()->delete();
    }

    /**
     * Handle the Comment "restored" event.
     *
     * @param  \App\Models\Comment  $Comment
     * @return void
     */
    public function restored(Comment $Comment)
    {
        //
    }

    /**
     * Handle the Comment "force deleted" event.
     *
     * @param  \App\Models\Comment  $Comment
     * @return void
     */
    public function forceDeleted(Comment $Comment)
    {
        //
    }
}
