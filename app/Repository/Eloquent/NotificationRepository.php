<?php

namespace App\Repository\Eloquent;

use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\DB;

class NotificationRepository
{
    // Method Overloading
    public function __call($member, $arguments)
    {
        $numberOfArguments = count($arguments);
        $functionName = '';

        if($member == 'store' && $numberOfArguments >= 4){
            $functionName = 'storeOld';
        }

        if($member == 'store' && $numberOfArguments == 2){
            $functionName = 'storeNew';
        }

        if (method_exists($this, $functionName)) {
            return call_user_func_array(array($this, $functionName), $arguments);
        }

        throw new Exception("Method {$member} Not Found");
    }

    private function storeOld($users, $type, $scope, $userGroup, $payload = null, $entityType = null, $entityId = null, $from = null, $publishedAt = null)
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

    private function storeNew($users, $data)
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

        return $notification;
    }
}