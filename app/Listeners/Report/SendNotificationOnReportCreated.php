<?php

namespace App\Listeners\Report;

use App\Events\Report\ReportCreated;
use App\Events\VideoViewed;
use App\Http\Resources\Comment\CommentResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\ReportComment;
use App\Notifications\ReportVideo;
use App\Notifications\TCNotification\TCNotification;

class SendNotificationOnReportCreated
{

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(ReportCreated $event)
    {
        $report = $event->report;
        $model = $event->model;

        $model_name = get_class($model) == Comment::class? 'comment': 'video';

        $notification = Notification::where([
            'entity_type' => get_class($model),
            'entity_id' => $model->id,
            'type' => 'ReportVideo',
            'scope' => Notification::SCOPE_ADMIN,
        ])->first();

        if ($notification){
            $payload = [
                $model_name => $model_name == 'video'? VideoResource::make($model): CommentResource::make($model),
                'report' => $report,
                'report_count' => $notification->payload['report_count'] + 1
            ];
            $notification->payload = $payload;
            $notification->save();
        }else{
            $admins = User::admins()->get();
            $payload = [
                $model_name => $model_name == 'video'? VideoResource::make($model): CommentResource::make($model),
                'report' => $report,
                'report_count' => 1
            ];

            if($model_name == 'video'){
                TCNotification::send($admins, new ReportVideo(
                    Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
                    Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                    $payload,
                    get_class($model),
                    $model->id
                ));
            }else{
                TCNotification::send($admins, new ReportComment(
                    Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
                    Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                    $payload,
                    get_class($model),
                    $model->id
                ));
            }
        }

        return true;
    }
}
