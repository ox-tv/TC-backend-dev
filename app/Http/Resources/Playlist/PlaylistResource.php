<?php

namespace App\Http\Resources\Playlist;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\User\UserResource;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
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
            'created_at' => $this->created_at,
            'url_hash' => $this->url_hash,

            // Custom attributes without query
            'status' => $this->status_text,

            // Custom attributes with query
            'all_videos_count' => $this->whenAppended('total_videos_count'),
            'published_videos_count' => $this->whenAppended('published_videos_count'),

            // Relations
            'owner' => UserResource::make($this->whenLoaded('owner')),
            'channel' => ChannelResource::make($this->whenLoaded('channel')),
        ];
    }
}
