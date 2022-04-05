<?php

namespace App\Http\Resources\Chapter;

use App\Http\Resources\Video\VideoResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
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
            'from' => $this->from,
            'title' => $this->title,
            'created_at' => $this->created_at,

            // Custom attributes without query

            // Custom attributes with query

            // Relations
            'video' => VideoResource::make($this->whenLoaded('video')),
        ];
    }
}
