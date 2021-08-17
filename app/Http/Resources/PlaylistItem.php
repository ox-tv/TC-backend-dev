<?php

namespace App\Http\Resources;

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
        $withVideos = in_array('videos', explode(',', $request->get('include', '')));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'url_hash' => $this->url_hash,
            'status' => Playlist::STATUS_TEXT[$this->status]??'',
            'videos' => $this->when($withVideos, VideoSummaryCollection::make($this->videos)),
        ];
    }
}
