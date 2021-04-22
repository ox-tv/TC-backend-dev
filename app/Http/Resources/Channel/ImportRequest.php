<?php

namespace App\Http\Resources\Channel;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportRequest extends JsonResource
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
            'youtube_channel_id' => $this->youtube_channel_id,
            'user_id' => $this->owner->id
        ];
    }
}
