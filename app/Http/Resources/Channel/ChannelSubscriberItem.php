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
            'cover' => $this->cover_url? :$this->cover,
            'cover_thumbnails' => $this->cover_url? getThumbnails($this->cover_url):[],
            'avatar' => $this->avatar_url? :$this->avatar,
            'avatar_thumbnails' => $this->avatar_url? getThumbnails($this->avatar_url):[],
            "slogan" => $this->slogan,
            "status" => $this->status ? Channel::STATUS_TEXT[$this->status] : null,
            'is_subscribed' => auth('api')->check() ? ($this->subscribers()->find(auth('api')->id()) ? true : false) : false,
        ];
    }
}
