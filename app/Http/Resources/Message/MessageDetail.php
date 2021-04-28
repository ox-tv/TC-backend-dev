<?php

namespace App\Http\Resources\Message;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageDetail extends JsonResource
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
            'parent_id' => $this->parent_id,
            'user' => $this->user,
            'type' => $this->type,
            'status' => $this->status,
            'can_reply' => $this->can_reply,
            'user_group' => $this->user_group,
            'department' => $this->department->name?? "",
            'replies' => $this->replies()->with("user")->get(),
            'created_at' => $this->created_at
        ];
    }
}
