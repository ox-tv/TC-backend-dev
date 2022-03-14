<?php

namespace App\Http\Resources\Category;

use App\Http\Resources\Video\VideoResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'slug' => $this->slug,

            // Custom attributes without query

            // Custom attributes with query

            // Relations
            'videos' => VideoResource::collection($this->whenLoaded('main_videos')),
        ];
    }
}
