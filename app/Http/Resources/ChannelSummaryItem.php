<?php

namespace App\Http\Resources;

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

        $subscribersCount = $this->subscribers->count();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'points' => $this->points,
            'subscribers_count' => $subscribersCount,
            'uploads_count' => $this->uploads_count,
            'total_views' => $this->total_views,
            'total_likes' => $this->total_likes,
            'hero_subscribers_count' => (int) ($subscribersCount * 0.7),
            'url_hash' => $this->url_hash,
            'avatar' => $this->avatar,
            "slogan" => $this->slogan,
            'is_subscribed' => auth('api')->check() ? ($this->subscribers()->find(auth('api')->user()->id) ? true : false) : false,
        ];
    }
}
