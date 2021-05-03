<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VideoCommentItem extends JsonResource
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
            'likes_count' => $this->likedBy()->count(),
            'dislikes_count' => $this->dislikedBy()->count(),
            'is_pinned' => (bool)$this->is_pinned,
            'is_liked' => $this->is_liked,
            'is_disliked' => $this->is_disliked,
            'user' => new UserItem($this->user),
            'replies_count' => $this->replies()->count(),
            'replies' => $this->when($withReplies, CommentCollection::make($this->replies)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
