<?php

namespace App\Http\Resources\Notification;

use App\Http\Resources\Department\DepartmentItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\Message;
use App\Models\MessageUser;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withUser = $this->relationLoaded('notifiable');

        $user = ($withUser)? UserMinimalItem::make($this->notifiable) : null;

        return [
            'id' => $this->id,
            'type' => $this->data['type']?? null,
            'payload' => @$this->data['payload']?? null,
            'from' => @$this->data['from']?? null,
            'updated_at' => $this->updated_at,
            'read_at' => $this->read_at,
            'user' => $this->when($withUser, $user),
        ];
    }
}
