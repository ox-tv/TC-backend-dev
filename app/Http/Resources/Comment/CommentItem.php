<?php

namespace App\Http\Resources\Comment;

use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Http\Resources\Video\VideoMinimalItem;
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
        $withUser = $this->relationLoaded('user');
        $withVideo = $this->relationLoaded('video');
        $withReplies = $this->relationLoaded('replies');
        $withReports = $this->relationLoaded('reports');

        $user = ($withUser)? UserMinimalItem::make($this->user) : [];
        $video = ($withVideo)? VideoMinimalItem::make($this->video) : [];
        $replies = ($withReplies)? CommentItem::collection($this->replies()->with(["user", "replies"])->get()) : [];
        $reports = ($withReports)? ReportMinimalItem::collection($this->reports) : [];

        return [
            'id' => $this->id,
            'text' => $this->text,
            'status' => $this->status,
            'is_pinned' => (bool) $this->is_pinned,
            'likes_count' => $this->likedBy()->count(),
            'dislikes_count' => $this->dislikedBy()->count(),
            'is_liked' => $this->is_liked,
            'is_disliked' => $this->is_disliked,
            'user' => $this->when($withUser, $user),
            'video' => $this->when($withVideo, $video),
            'replies_count' => $this->replies()->count(),
            'reports_count' => $this->reports_count,
            'replies' => $this->when($withReplies, $replies),
            'reports' => $this->when($withReports, $reports),
            'reason_key' => $this->when($this->reason_key, $this->reason_key),
            'reason_text' => $this->when($this->reason_text, $this->reason_text),
            'created_at' => $this->created_at,
        ];
    }
}
