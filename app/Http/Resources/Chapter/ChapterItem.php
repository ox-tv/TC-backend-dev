<?php

namespace App\Http\Resources\Chapter;

use Illuminate\Http\Resources\Json\JsonResource;

class ChapterItem extends JsonResource
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
            'from' => $this->from,
            'title' => $this->title,
            'created_at' => $this->created_at
        ];
    }
}
