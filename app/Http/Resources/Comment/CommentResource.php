<?php

namespace App\Http\Resources\Comment;

use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\Video\VideoResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            // Main attributes
            'id' => $this->id,
            'text' => $this->text,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'parent_id' => $this->parent_id,
            'last_mentioned_at' => $this->whenAppended('last_mentioned_at'),
            'reason_key' => $this->whenAppended('reason_key'),
            'reason_text' => $this->whenAppended('reason_text'),

            // Custom attributes without query
            'is_pinned' => $this->is_pinned,

            // Custom attributes with query
            'is_liked' => $this->whenAppended('is_liked'),
            'is_disliked' => $this->whenAppended('is_disliked'),
            'is_remembered' => $this->whenAppended('is_remembered'),
            'reports_count' => $this->whenAppended('reports_count'),
            'likes_count' => $this->whenAppended('likes_count'),
            'dislikes_count' => $this->whenAppended('dislikes_count'),
            'replies_count' => $this->whenAppended('replies_count'),
            'is_read_replies' => $this->whenAppended('is_read_replies'),

            // Relations
            'pinned_by' => UserResource::make($this->whenLoaded('PinnedBy')),
            'user' => UserResource::make($this->whenLoaded('user')),
            'video' => VideoResource::make($this->whenLoaded('video')),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'reports' => ReportMinimalItem::collection($this->whenLoaded('reports')),
            'mentions' => UserResource::collection($this->whenLoaded('mentions')),
        ];
    }
}
