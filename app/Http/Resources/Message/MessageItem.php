<?php

namespace App\Http\Resources\Message;

use App\Http\Resources\Department\DepartmentItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\Message;
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
        $withFrom = $this->relationLoaded('user');
        $withTo = $this->relationLoaded('users');
        $withDepartment = $this->relationLoaded('department');
        $withReplies = $this->relationLoaded('replies');


        $user = ($withFrom)? UserMinimalItem::make($this->user) : null;

        if($this->users()->where('users.id', $this->user->id)->exists()){
            $users = [];
        }else{
            $users = ($withTo)? UserMinimalItem::collection($this->users) : null;
        }

        $department = ($withDepartment)? DepartmentItem::make($this->department) : null;
        $replies = ($withReplies)? MessageItem::collection($this->replies()->with("user")->get()) : [];

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'message' => $this->message,
            'image' => $this->image,
            'from' => $this->when($withFrom, $user),
            'to' => $this->when($withTo, $users),
            'type' => $this->type,
            'status' => $this->status? MessageUser::STATUS_TEXT[$this->status]: null,
            'can_reply' => $this->can_reply,
            'parent_id' => $this->parent_id,
            'user_group' => Message::USER_GROUP_TEXT[$this->user_group]?? null,
            'department' => $this->when($withDepartment, $department),
            'created_at' => $this->created_at,
            'replies' => $this->when($withReplies, $replies),
        ];
    }
}
