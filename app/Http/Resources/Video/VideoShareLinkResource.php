<?php

namespace App\Http\Resources\Video;

use Illuminate\Http\Resources\Json\JsonResource;


class VideoShareLinkResource extends JsonResource
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
            'video_id' => $this->whenAppended('video_id'),
            'user_id' => $this->whenAppended('user_id'),
            'count' => $this->count,
            'created_at' => $this->created_at,

            // Custom attributes without query

            // Custom attributes with query
            'total_views' => $this->whenAppended('totalViews'),

            // Relations
            'video' => VideoResource::make($this->whenLoaded('video')),
        ];
    }
}
