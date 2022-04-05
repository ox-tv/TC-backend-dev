<?php


namespace App\Repository\Eloquent;


use App\Models\Notification;
use App\Repository\NotificationRepositoryInterface;
use Illuminate\Support\Facades\DB;

class NotificationRepository implements NotificationRepositoryInterface
{

    public function store($users, $type, $scope, $userGroup, $payload = null, $entityType = null, $entityId = null, $from = null, $publishedAt = null)
    {
        $notification = new Notification();
        $notification->type = $type;
        $notification->scope = array_flip(Notification::SCOPE_TEXT)[$scope];
        $notification->payload = $payload;
        $notification->user_group = array_flip(Notification::USER_GROUP_TEXT)[$userGroup];
        $notification->sender_id = $from;
        $notification->entity_type = $entityType;
        $notification->entity_id = $entityId;
        $notification->published_at = $publishedAt;

        DB::transaction(function () use ($notification, $users){
            $notification->save();
            $notification->users()->attach($users->pluck('id')->toArray());
        });

        return true;
    }
}