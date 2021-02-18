<?php

namespace App\Http\Resources;

use App\Http\Resources\Category\CategoryCollection;
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
            'view_count' => $this->view_count,
            'comment_count' => $this->comments()->count(),
            'likes_count' => $this->likedBy()->count(),
            'dislikes_count' => $this->dislikedBy()->count(),
            'is_liked' => $this->is_liked,
            'is_disliked' => $this->is_disliked,
            'duration' => $this->duration,
            'status' => $this->status ? Video::STATUS_TEXT[$this->status] : null,
            'user' => new UserItem($this->user),
            'channel' => new ChannelItem($this->channels->first()),
            'categories' => CategoryCollection::make($this->categories),
            'tags' => $this->tags->map(function($tag){ return $tag->name; }),
            'playlists' => $this->playlists->map(function($tag){ return $tag->id; }),
            'comments' => $this->when($withComments ,VideoCommentCollection::make($this->comments()->paginate(50))->response()->getData(true)),
            'created_at' => $this->created_at,
            'published_at' => $this->published_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'published_at' => $this->published_at,
        ];
    }
}
