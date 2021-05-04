<?php

namespace App\Http\Resources\Playlist;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistMinimalItem extends JsonResource
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
            'url_hash' => $this->url_hash,
        ];
    }
}
