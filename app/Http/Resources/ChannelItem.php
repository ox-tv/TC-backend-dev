<?php

namespace App\Http\Resources;

use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelItem extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'subscribers_count' => $this->subscribers->count(),
            'uploads_count' => $this->uploads_count,
            'total_views' => $this->total_views,
            'total_likes' => $this->total_likes,
            'total_dislikes' => $this->total_dislikes,
            'comments_count' => $this->total_comments,
            'points' => $this->points,
            'watch_time' => $this->videos()->sum("watch_time"),
            'hero_subscribers_count' => $this->heroSubscribers->count(),
            'url_hash' => $this->url_hash,
            'cover' => $this->cover,
            'cover_thumbnails' => $this->cover_thumbnails,
            'avatar' => $this->avatar,
            'avatar_thumbnails' => $this->avatar_thumbnails,
            "instagram" => $this->instagram,
            "facebook" => $this->facebook,
            "twitter" => $this->twitter,
            "website" => $this->website,
            "slogan" => $this->slogan,
            "owner" => UserItem::make($this->owner),
            "status" => $this->status ? Channel::STATUS_TEXT[$this->status] : null,
            "import_request_status" => Channel::IMPORT_STATUS_TEXT[$this->import_request_status]?? null,
            'is_subscribed' => auth('api')->check() && $this->subscribers()->where('user_id', auth('api')->id())->exists(),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
