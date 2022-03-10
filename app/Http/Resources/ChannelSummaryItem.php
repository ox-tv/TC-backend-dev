<?php

namespace App\Http\Resources;

use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelSummaryItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withOwner = in_array('owner', explode(',', $request->get('include', '')));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'subscribers_count' => $this->subscribers->count(),
            'uploads_count' => $this->uploads_count,
            'total_views' => $this->total_views,
            'total_likes' => $this->total_likes,
            'total_dislikes' => $this->total_dislikes,
            'comments_count' => $this->total_comments,
            'points' => $this->points,
            'watch_time' => $this->videos()->sum("watch_time"),
            'hero_subscribers_count' => $this->heroSubscribers->count(),
            "owner" => $this->when($withOwner, UserItem::make($this->owner)),
            'url_hash' => $this->url_hash,
            'cover' => $this->cover_url? :$this->cover,
            'cover_thumbnails' => $this->cover_url? getThumbnails($this->cover_url):[],
            'avatar' => $this->avatar_url? :$this->avatar,
            'avatar_thumbnails' => $this->avatar_url? getThumbnails($this->avatar_url):[],
            "slogan" => $this->slogan,
            "status" => $this->status ? Channel::STATUS_TEXT[$this->status] : null,
            "import_request_status" => Channel::IMPORT_STATUS_TEXT[$this->import_request_status]?? null,
            'is_subscribed' => auth('api')->check() && $this->subscribers()->where('user_id', auth('api')->id())->exists(),
        ];
    }
}
