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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subscribers_count' => $this->subscribers->count(),
            'url_hash' => $this->url_hash,
            'avatar' => $this->avatar,
            "slogan" => $this->slogan,
            'is_subscribed' => auth('api')->check() ? ($this->subscribers()->find(auth('api')->user()->id) ? true : false) : false,
        ];
    }
}
