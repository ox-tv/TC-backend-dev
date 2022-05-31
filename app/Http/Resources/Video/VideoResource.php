<?php

namespace App\Http\Resources\Video;

use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Chapter\ChapterResource;
use App\Http\Resources\Comment\CommentResource;
use App\Http\Resources\CryptoCurrency\CryptoCurrencyResource;
use App\Http\Resources\Language\LanguageResource;
use App\Http\Resources\Playlist\PlaylistResource;
use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\Subtitle\SubtitleResource;
use App\Http\Resources\Tag\TagResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class VideoResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'url_hash' => $this->url_hash,
            'description' => $this->description,
            'duration' => $this->duration,
            'user_id' => $this->user_id,
            'view_count' => $this->view_count,
            'category_id' => $this->category_id,
            'created_at' => $this->created_at,
            'published_at' => $this->published_at,
            'watch_time' => $this->watch_time,
            'reason_key' => $this->whenAppended('reason_key'),
            'reason_text' => $this->whenAppended('reason_text'),
            'channel_id' => $this->channel_id,
            'language_id' => $this->language_id,

            // Custom attributes without query
            'media_type' => $this->media_type_text,
            'is_published' => $this->is_published,
            'status' => $this->status_text,
            'url' => $this->file_url,
            'file_type' => $this->file_type,
            'thumbnail' => $this->thumbnail_url,
            'thumbnails' => $this->thumbnails,

            // Custom attributes with query
            'rating' => $this->whenAppended('rating'),
            'comment_count' => $this->whenAppended('comment_count'),
            'likes_count' => $this->whenAppended('likes_count'),
            'dislikes_count' => $this->whenAppended('dislikes_count'),
            'reports_count' => $this->whenAppended('reports_count'),
            'is_liked' => $this->whenAppended('is_liked'),
            'is_disliked' => $this->whenAppended('is_disliked'),
            'is_bookmarked' => $this->whenAppended('is_bookmarked'),
            'layers' => $this->whenAppended('layers'),
            'pinned_comment' => CommentResource::make($this->whenAppended('pinnedComment')),

            // Relations
            'user' => UserResource::make($this->whenLoaded('user')),
            'channel' => ChannelResource::make($this->whenLoaded('channel')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'language' => LanguageResource::make($this->whenLoaded('language')),
            'crypto_currencies' => CryptoCurrencyResource::collection($this->whenLoaded('crypto_currencies')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'playlists' => PlaylistResource::collection($this->whenLoaded('playlists')),
            'subtitles' => SubtitleResource::collection($this->whenLoaded('subtitles')),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'reports' => ReportMinimalItem::collection($this->whenLoaded('reports')),
            'meta' => VideoMetaResource::collection($this->whenLoaded('meta')),
//            'overlays' => VideoMetaResource::make($this->whenLoaded('layers')),
//            'overlays_draft' => VideoMetaResource::make($this->whenLoaded('layersDraft')),
        ];
    }
}
