<?php


namespace App\Notifications\TCNotification;


use App\Repository\Eloquent\NotificationRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class TCNotification
{
    public static function send($users, $notification)
    {
        $notificationRepository = new NotificationRepository();
        $notificationRepository->store(
            $users,
            $notification->type,
            $notification->scope,
            $notification->userGroup,
            $notification->payload,
            $notification->entityType,
            $notification->entityId,
            $notification->from,
            $notification->publishedAt?? Carbon::now(),
        );

        Notification::send($users, $notification);
    }
}