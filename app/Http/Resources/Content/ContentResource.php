<?php

namespace App\Http\Resources\Content;

use App\Http\Resources\User\UserResource;
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
            'key' => $this->key,
            'content' => $this->content,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,

            // Custom attributes without query
            'status' => $this->whenAppended('status_text'),

            // Custom attributes with query

            // Relations
            'last_modified_by' => UserResource::make($this->whenLoaded('lastModifiedBy')),
        ];
    }
}
