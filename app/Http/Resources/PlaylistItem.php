<?php

namespace App\Http\Resources;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Models\Playlist;
use App\Models\Video;
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

        if ($request->is('api/admin/*') || $request->is('api/my-playlists')){
            $videos_count = $this->videos()->count();
        }else{
            $videos_count = $this->videos()->where('status', Video::STATUS_PUBLISHED)->count();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'url_hash' => $this->url_hash,
            'videos_count' => $videos_count,
            'status' => Playlist::STATUS_TEXT[$this->status]??'',
            'channel' => $this->when($withChannel, ChannelMinimalItem::make($this->owner->channel)),
        ];
    }
}
