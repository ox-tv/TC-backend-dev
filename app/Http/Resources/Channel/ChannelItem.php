<?php

namespace App\Http\Resources\Channel;

use App\Http\Resources\User\UserMinimalItem;
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
        $include = explode(',', $request->get('include', ''));

        $withOwner = in_array('owner', $include) || $this->relationLoaded('owner');

        $owner = ($withOwner)? UserMinimalItem::make($this->owner) : [];


        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
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
            "owner" => $this->when($withOwner, $owner),
            "status" => $this->status ? Channel::STATUS_TEXT[$this->status] : null,
            'points' => $this->points,
            "import_request_status" => Channel::IMPORT_STATUS_TEXT[$this->import_request_status]?? null,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,

            'uploads_count' => $this->uploads_count,
            'total_views' => $this->total_views,
            'watch_time' => $this->watch_time,
            'is_subscribed' => auth('api')->check() && $this->subscribers()->where('user_id', auth('api')->id())->exists(),
            'subscribers_count' => $this->subscribers()->count(),
            'hero_subscribers_count' => $this->heroSubscribers()->count(),
            'total_likes' => $this->total_likes,
            'total_dislikes' => $this->total_dislikes,
            'comments_count' => $this->total_comments,
        ];
    }
}
