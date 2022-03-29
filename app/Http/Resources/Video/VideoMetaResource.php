<?php

namespace App\Http\Resources\Video;

use Illuminate\Http\Resources\Json\JsonResource;


class VideoMetaResource extends JsonResource
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
            'key' => $this->key,
            'value' => $this->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Custom attributes without query

            // Custom attributes with query

            // Relations
            'video' => VideoResource::make($this->whenLoaded('video')),
        ];
    }
}
