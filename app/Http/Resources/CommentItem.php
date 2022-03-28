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
            'is_pinned' => (bool) $this->is_pinned,
            'pinned_by' => ($this->is_pinned)? $this->PinnedBy : null,
            'likes_count' => $this->likedBy()->count(),
            'dislikes_count' => $this->dislikedBy()->count(),
            'is_liked' => $this->is_liked,
            'is_disliked' => $this->is_disliked,
            'user' => new UserItem($this->user),
            'video' => new VideoSummaryItem($this->video),
            'reports_count' => $this->reports_count,
            'reason_key' => $this->when($this->reason_key, $this->reason_key),
            'reason_text' => $this->when($this->reason_text, $this->reason_text),
            'replies_count' => $this->replies()->count(),
            'replies' => $this->when($withReplies, CommentSummaryCollection::make($this->replies)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
