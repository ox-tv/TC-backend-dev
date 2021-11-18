<?php

namespace App\Http\Resources\Tag;

use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

class TagItem extends JsonResource
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
            'status' => Tag::STATUS_TEXT[$this->status]?? null,
            'creation_scope' => Tag::CREATION_SCOPE_TEXT[$this->creation_scope]?? null,
        ];
    }
}
