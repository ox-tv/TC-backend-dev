<?php

namespace App\Http\Resources\Content;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
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
            'page' => $this->page,
            'content' => $this->content,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,

            // Custom attributes without query

            // Custom attributes with query

            // Relations
        ];
    }
}
