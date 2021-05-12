<?php

namespace App\Http\Resources;

use App\Http\Resources\Category\CategoryCollection;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class VideoSummaryItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withComments = in_array('comments', explode(',', $request->get('include', '')));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'url' => $this->upload_method == Video::UPLOAD_METHOD_DIRECT ? Storage::disk('videos')->url($this->file_path) : $this->youtube_link,
            'url_hash' => $this->url_hash,
            'thumbnail' => $this->thumbnail,
            'rating' => $this->rating,
            'is_bookmarked' => $this->is_bookmarked,
            'view_count' => $this->view_count,
            'duration' => $this->duration,
            'watch_time' => $this->watch_time,
            'status' => $this->status ? Video::STATUS_TEXT[$this->status] : null,
            'channel' => ChannelSummaryItem::make($this->channels->first()),
            'category' => $this->category,
            'categories' => $this->categories,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'published_at' => $this->published_at,
        ];
    }
}
