<?php

namespace App\Repository\Eloquent;

use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NotificationRepository
{

    public function store($users, $data)
    {
        $notification = new Notification();
        $notification->type = $data['type'];
        $notification->scope = $data['scope'];
        $notification->payload = $data['payload'];
        $notification->user_group = $data['user_group'];
        $notification->sender_id = $data['sender_id'];
        $notification->entity_type = $data['entity_type'];
        $notification->entity_id = $data['entity_id'];
        $notification->published_at = $data['published_at'];

        DB::transaction(function () use ($notification, $users){
            $notification->save();
            $notification->users()->attach($users->pluck('id')->toArray());
        });

        foreach ($users as $user){
            Cache::forget("user.{$user->id}.notifications.unread_count");
        }

        return $notification;
    }
}