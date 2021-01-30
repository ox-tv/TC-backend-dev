<?php

namespace App\Http\Resources;

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
            'url_hash' => $this->url_hash,
            'cover' => $this->cover ?? asset('assets/images/default-video-thumbnail.png'),
            'avatar' => $this->avatar ?? asset('assets/images/default-avatar.png'),
            "instagram" => $this->instagram,
            "facebook" => $this->facebook,
            "twitter" => $this->twitter,
            "website" => $this->website,
            "slogan" => $this->slogan,
            "user" => $this->owner,
            "status" => $this->status,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
