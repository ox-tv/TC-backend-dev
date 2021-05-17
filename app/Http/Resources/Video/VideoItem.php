<?php

namespace App\Http\Resources\Video;

use App\Http\Resources\Category\CategoryCollection;
use App\Http\Resources\Category\CategoryItem;
use App\Http\Resources\Category\CategoryMinimalCollection;
use App\Http\Resources\Category\CategoryMinimalItem;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Playlist\PlaylistMinimalCollection;
use App\Http\Resources\Playlist\PlaylistMinimalItem;
use App\Http\Resources\Report\ReportItem;
use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Http\Resources\VideoCommentCollection;
use App\Http\Resources\VideoSummaryCollection;
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
        $include = explode(',', $request->get('include', ''));

        $withUser = in_array('user', $include) || $this->relationLoaded('user');
        $withChannel = in_array('channel', $include) || $this->relationLoaded('channels');
        $withMainCategory = in_array('category', $include) || $this->relationLoaded('category');
        $withCategories = in_array('categories', $include) || $this->relationLoaded('categories');
        $withTags = in_array('tags', $include) || $this->relationLoaded('tags');
        $withPlaylists = in_array('playlists', $include) || $this->relationLoaded('playlists');
        $withReports = in_array('reports', $include) || $this->relationLoaded('reports');

        $user = ($withUser)? UserMinimalItem::make($this->user) : [];
        $channel = ($withChannel)? ChannelMinimalItem::make($this->channels->first()) : [];
        $category = ($withMainCategory)? CategoryMinimalItem::make($this->category) : [];
        $categories = ($withCategories)? CategoryMinimalItem::collection($this->categories) : [];
        $tags = ($withTags)? $this->tags : [];
        $playlists = ($withPlaylists)? PlaylistMinimalItem::collection($this->playlists) : [];
        $reports = ($withReports)? ReportMinimalItem::collection($this->reports) : [];

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
            'is_published' => $this->is_published,
            'rating' => $this->rating,
            'comment_count' => $this->comment_count,
            'likes_count' => $this->likes_count,
            'dislikes_count' => $this->dislikes_count,
            'is_liked' => $this->is_liked,
            'is_disliked' => $this->is_disliked,
            'is_bookmarked' => $this->is_bookmarked,
            'watch_time' => $this->watch_time,
            'user' => $this->when($withUser, $user),
            'channel' => $this->when($withChannel, $channel),
            'categories' => $this->when($withCategories, $categories),
            'category' => $this->when($withMainCategory, $category),
            'tags' => $this->when($withTags, $tags),
            'playlists' => $this->when($withPlaylists, $playlists),
            'reports' => $this->when($withReports, $reports),
        ];
    }
}
