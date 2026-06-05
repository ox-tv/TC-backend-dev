<?php

namespace App\Http\Resources\Channel;

use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelSubscriberItem extends JsonResource
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
            'slug' => $this->slug,
            'subscribers_count' => $this->subscribers->count(),
            'url_hash' => $this->url_hash,
            'cover' => $this->cover,
            'cover_thumbnails' => $this->cover_thumbnails,
            'avatar' => $this->avatar,
            'avatar_thumbnails' => $this->avatar_thumbnails,
            "slogan" => $this->slogan,
            "status" => $this->status ? Channel::STATUS_TEXT[$this->status] : null,
            'is_subscribed' => auth('api')->check() && $this->subscribers()->where('user_id', auth('api')->id())->exists(),
        ];
    }
}
