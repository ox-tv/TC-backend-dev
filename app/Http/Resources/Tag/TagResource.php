<?php

namespace App\Http\Resources\Tag;

use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
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
            'created_at' => $this->created_at,

            // Custom attributes without query
            'status' => $this->status_text,
            'creation_scope' => $this->creation_scope_text,

            // Custom attributes with query
            'favorited_count' => $this->whenAppended('favorited_by_users_count'),
            'videos_count' => $this->whenAppended('videos_count'),

            // Relations

        ];
    }
}
