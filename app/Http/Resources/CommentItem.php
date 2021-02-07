<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $withReplies = in_array('replies', explode(',', $request->get('include', '')));

        return [
            'id' => $this->id,
            'text' => $this->text,
            'status' => $this->status,
            'user' => new UserItem($this->user),
            'video' => new VideoItem($this->video),
            'replies' => $this->when($withReplies, CommentCollection::make($this->replies)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
