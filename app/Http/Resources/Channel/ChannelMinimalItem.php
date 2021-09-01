<?php

namespace App\Http\Resources\Channel;

use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelMinimalItem extends JsonResource
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
            'url_hash' => $this->url_hash,
            'cover' => $this->cover,
            'avatar' => $this->avatar,
            "instagram" => $this->instagram,
            "facebook" => $this->facebook,
            "twitter" => $this->twitter,
            "website" => $this->website,
            "slogan" => $this->slogan,
            "user_id" => $this->user_id,
            "status" => Channel::STATUS_TEXT[$this->status]?? null,
            'points' => $this->points,
            "import_request_status" => Channel::IMPORT_STATUS_TEXT[$this->import_request_status]?? null,
            'youtube_channel_id' => $this->youtube_channel_id,
            'youtube_channel_url' => $this->youtube_channel_url,
            "created_at" => $this->created_at,

            'is_subscribed' => auth('api')->check() ?
                ($this->subscribers()->where('user_id', auth('api')->id())->exists() ? true : false) : false,
            'subscribers_count' => $this->subscribers()->count(),
        ];
    }
}
