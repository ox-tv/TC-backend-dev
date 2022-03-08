<?php

namespace App\Http\Resources\Video;

use App\Http\Resources\Category\CategoryCollection;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class VideoCardItem extends JsonResource
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
            'description' => $this->description,
            'slug' => $this->slug,
            'url' => $this->file_url? : Storage::disk('videos')->url($this->file_path),
            'url_hash' => $this->url_hash,
            'thumbnail' => $this->thumbnail_url? :$this->thumbnail,
            'thumbnails' => $this->thumbnail_url? getThumbnails($this->thumbnail_url):[],
            'view_count' => $this->view_count,
            'duration' => $this->duration,
            'status' => $this->status ? Video::STATUS_TEXT[$this->status] : null,
            'published_at' => $this->published_at,

            'channel' => ChannelMinimalItem::make($this->channel()->first()),
            'is_bookmarked' => $this->is_bookmarked,
        ];
    }
}
