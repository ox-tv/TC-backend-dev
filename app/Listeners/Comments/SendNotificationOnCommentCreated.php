<?php

namespace App\Listeners\Comments;

use Amir\Permission\Models\Role;
use App\Events\Comments\CommentCreated;
use App\Http\Resources\Comment\CommentResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Notification;
use App\Models\User;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnCommentCreated
{
    public function handle(CommentCreated $event)
    {
        $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;

        $comment = $event->comment;
        $video = $comment->video()->first();
        $mentions = $comment->mentions()->notPublishers()->get();

        $comment->load('user');

        if ($mentions->isNotEmpty()){
            TCNotification::Send($mentions, new GeneralNotification(
                Notification::TYPE_MENTIONED_ON_COMMENT,
                Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
                [
                    'comment' => CommentResource::make($comment),
                    'video' => VideoResource::make($video),
                ]
            ));
        }

        return true;
    }
}
