<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Comment;
use App\Models\CommentUser;
use App\Models\User;
use App\Models\UserVideo;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChannelDemoEngagement extends Command
{
    private const DEMO_PREFIX = '[demo_engagement]';

    protected $signature = 'channel:demo-engagement
                            {channel_id : MySQL channel id (e.g. 20227)}
                            {--purge : Remove demo-marked comments and their comment_user rows first}';

    protected $description = 'Adds sample comments, comment likes/dislikes, and video likes/dislikes across all published videos of a channel (local/demo).';

    public function handle(): int
    {
        $channelId = (int) $this->argument('channel_id');
        $channel = Channel::query()->find($channelId);
        if (!$channel) {
            $this->error("Channel {$channelId} not found.");

            return 1;
        }

        $videoIds = Video::withoutGlobalScopes()
            ->where('channel_id', $channelId)
            ->where('status', Video::STATUS_PUBLISHED)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if (count($videoIds) === 0) {
            $this->warn('No published videos on this channel. Nothing to do.');

            return 0;
        }

        if ($this->option('purge')) {
            $this->purgeDemoComments($videoIds);
        }

        $ownerId = $channel->user_id;
        $pool = User::query()
            ->withoutTrashed()
            ->whereKeyNot($ownerId)
            ->inRandomOrder()
            ->limit(150)
            ->pluck('id')
            ->all();

        if (count($pool) < 40) {
            $this->error('Need at least 40 other users in the database to spread likes cleanly. Found ' . count($pool) . '.');

            return 1;
        }

        $commentBodies = [
            'Really solid take — thanks for breaking this down.',
            'Been following this narrative; curious what you think about regulation next.',
            'Great visuals and pacing on this one.',
            'This aged well. Subscribed.',
            'Can you cover the risks in a follow-up?',
            'Helpful episode, shared with my group chat.',
            'Clear explanation — exactly what I was looking for.',
            'Disagree slightly on the timing but good analysis overall.',
            'More content like this please.',
            'Bookmarked. The section at the end was especially useful.',
        ];

        $poolIdx = 0;
        $nextUser = function () use (&$poolIdx, $pool) {
            $id = $pool[$poolIdx % count($pool)];
            $poolIdx++;

            return $id;
        };

        $commentsCreated = 0;
        $videoReactions = 0;
        $commentReactions = 0;

        DB::transaction(function () use (
            $videoIds,
            $nextUser,
            $commentBodies,
            &$commentsCreated,
            &$videoReactions,
            &$commentReactions
        ) {
            foreach ($videoIds as $vIndex => $videoId) {
                $numComments = 3 + ($vIndex % 4);
                for ($c = 0; $c < $numComments; $c++) {
                    $body = self::DEMO_PREFIX . ' ' . $commentBodies[($vIndex + $c) % count($commentBodies)];
                    $authorId = $nextUser();

                    $comment = new Comment();
                    $comment->text = $body;
                    $comment->status = 1;
                    $comment->user_id = $authorId;
                    $comment->video_id = $videoId;
                    $comment->parent_id = null;
                    $comment->save();
                    $commentsCreated++;

                    $likeTargets = min(4, 1 + ($c % 4));
                    for ($k = 0; $k < $likeTargets; $k++) {
                        $uid = $nextUser();
                        if ($uid === $authorId) {
                            $uid = $nextUser();
                        }
                        try {
                            if (! CommentUser::query()->where([
                                'comment_id' => $comment->id,
                                'user_id' => $uid,
                                'relation' => CommentUser::LIKED_RELATION,
                            ])->exists()) {
                                $cu = new CommentUser([
                                    'comment_id' => $comment->id,
                                    'user_id' => $uid,
                                    'relation' => CommentUser::LIKED_RELATION,
                                ]);
                                $cu->save();
                                $commentReactions++;
                            }
                        } catch (\Throwable $e) {
                            // ignore rare race
                        }
                    }

                    if ($vIndex % 5 === 0 && $c === 0) {
                        $uid = $nextUser();
                        if ($uid !== $authorId) {
                            if (! CommentUser::query()->where([
                                'comment_id' => $comment->id,
                                'user_id' => $uid,
                                'relation' => CommentUser::DISLIKED_RELATION,
                            ])->exists()) {
                                $cu = new CommentUser([
                                    'comment_id' => $comment->id,
                                    'user_id' => $uid,
                                    'relation' => CommentUser::DISLIKED_RELATION,
                                ]);
                                $cu->save();
                                $commentReactions++;
                            }
                        }
                    }
                }

                $likeCount = 6 + ($vIndex % 9);
                $used = [];
                for ($l = 0; $l < $likeCount; $l++) {
                    $uid = $nextUser();
                    $guard = 0;
                    while (isset($used[$uid]) && $guard < 25) {
                        $uid = $nextUser();
                        $guard++;
                    }
                    $used[$uid] = true;

                    if (! UserVideo::query()->where([
                        'video_id' => $videoId,
                        'user_id' => $uid,
                        'relation' => UserVideo::LIKED_RELATION,
                    ])->exists()) {
                        $row = new UserVideo();
                        $row->video_id = $videoId;
                        $row->user_id = $uid;
                        $row->relation = UserVideo::LIKED_RELATION;
                        $row->save();
                        $videoReactions++;
                    }
                }

                $dislikeCount = ($vIndex % 4 === 0) ? 2 : 1;
                for ($d = 0; $d < $dislikeCount; $d++) {
                    $uid = $nextUser();
                    $guard = 0;
                    while (isset($used[$uid]) && $guard < 25) {
                        $uid = $nextUser();
                        $guard++;
                    }
                    $used[$uid] = true;

                    if (! UserVideo::query()->where([
                        'video_id' => $videoId,
                        'user_id' => $uid,
                        'relation' => UserVideo::DISLIKED_RELATION,
                    ])->exists()) {
                        $row = new UserVideo();
                        $row->video_id = $videoId;
                        $row->user_id = $uid;
                        $row->relation = UserVideo::DISLIKED_RELATION;
                        $row->save();
                        $videoReactions++;
                    }
                }
            }
        });

        $this->info("Channel {$channelId}: {$commentsCreated} demo comments, {$commentReactions} comment votes, {$videoReactions} new video like/dislike rows.");

        return 0;
    }

    /**
     * @param  array<int>  $videoIds
     */
    protected function purgeDemoComments(array $videoIds): void
    {
        $ids = Comment::withoutGlobalScopes()
            ->whereIn('video_id', $videoIds)
            ->where('text', 'like', self::DEMO_PREFIX . '%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('comment_user')->whereIn('comment_id', $ids)->delete();
        Comment::withoutGlobalScopes()->whereIn('id', $ids)->forceDelete();

        $this->info('Removed ' . $ids->count() . ' prior demo comments and their comment_user rows.');
    }
}
