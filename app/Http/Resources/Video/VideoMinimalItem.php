<?php

namespace App\Http\Resources\Video;

use App\Http\Resources\Category\CategoryCollection;
use App\Http\Resources\Category\CategoryItem;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class VideoMinimalItem extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'url_hash' => $this->url_hash,
            'description' => $this->description,
            'url' => Storage::disk('videos')->url($this->file_path),
            'thumbnail' => $this->thumbnail,
            'status' => Video::STATUS_TEXT[$this->status]?? null,
            'duration' => $this->duration,
            'user_id' => $this->user_id,
            'view_count' => $this->view_count,
            'category_id' => $this->category_id,
            'created_at' => $this->created_at,
            'published_at' => $this->published_at,
            'is_published' => $this->published_at,
            'watch_time' => $this->watch_time,
            'reason_key' => $this->when($this->reason_key, $this->reason_key),
            'reason_text' => $this->when($this->reason_text, $this->reason_text),
        ];
    }
}
