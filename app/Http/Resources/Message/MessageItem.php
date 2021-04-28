<?php

namespace App\Http\Resources\Message;

use App\Models\MessageUser;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'message' => $this->message,
            'image' => $this->image,
            'type' => $this->type,
            'status' => MessageUser::STATUS_TEXT[$this->status],
            'can_reply' => $this->can_reply,
            'user_group' => $this->user_group,
            'department' => $this->department->name??"",
            'created_at' => $this->created_at
        ];
    }
}
