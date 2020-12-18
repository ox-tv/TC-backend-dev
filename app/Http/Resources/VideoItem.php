<?php

namespace App\Http\Resources;

use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VideoItem extends JsonResource
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
            'url' => $this->upload_method == Video::UPLOAD_METHOD_DIRECT ? Storage::disk('videos')->url($this->file_path) : $this->youtube_link,
            'categories' => CategoryCollection::make($this->categories),
            'thumbnail' => $this->thumbnail ? Storage::disk('videos_thumbnails')->url($this->thumbnail) : config('app.url').'/assets/images/default-video-thumbnail.png',
            'rating' => $this->rating,
            'user' => new UserItem($this->user),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'published_at' => $this->published_at,
        ];
    }
}
