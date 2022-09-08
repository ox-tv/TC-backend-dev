<?php

namespace App\Http\Resources\Channel;

use App\Http\Resources\User\UserResource;
use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportRequestResource extends JsonResource
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
            'youtube_channel_id' => $this->youtube_channel_id,
            'user_id' => $this->user_id,
            'youtube_last_scraped_at' => $this->youtube_last_scraped_at,
            'import_request_status' => Channel::IMPORT_STATUS_TEXT[$this->import_request_status]?? null,
        ];
    }
}
