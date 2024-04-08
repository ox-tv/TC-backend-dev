<?php

namespace App\Listeners\Report;

use App\Events\Report\ReportCreated;
use App\Http\Resources\Comment\CommentResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;
use App\TCNotification\GeneralNotification;
use TCNotification;

class SendNotificationOnReportCreated
{

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
                $notifType = Notification::TYPE_REPORT_VIDEO;
            }else{
                $notifType = Notification::TYPE_REPORT_COMMENT;
            }

            TCNotification::Send($admins, new GeneralNotification(
                $notifType,
                Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
                $payload,
                [
                    'entity_type' => get_class($model),
                    'entity_id' => $model->id,
                ]
            ));
        }

        return true;
    }
}
