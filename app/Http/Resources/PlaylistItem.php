<?php

namespace App\Http\Resources;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Models\Playlist;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withChannel = in_array('channel', explode(',', $request->get('include', '')));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'url_hash' => $this->url_hash,
            'videos_count' => $this->videos()->count(),
            'status' => Playlist::STATUS_TEXT[$this->status]??'',
            'channel' => $this->when($withChannel, ChannelMinimalItem::make($this->owner->channel)),
        ];
    }
}
