<?php

namespace App\Http\Resources\Channel;

use App\Http\Resources\User\UserResource;
use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelHomeResource extends JsonResource
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
            // Main attributes
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,

            // Custom attributes without query
            'avatar_thumbnails' => $this->avatar_thumbnails,

            // Custom attributes with query
            'is_subscribed' => $this->whenAppended('is_subscribed'),

            // Relations
        ];
    }
}
