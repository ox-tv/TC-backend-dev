<?php

namespace App\Http\Resources\Video;

use App\Http\Resources\Category\CategoryCollection;
use App\Http\Resources\Category\CategoryMinimalItem;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\CryptoCurrency\CryptoCurrencyItem;
use App\Http\Resources\Language\LanguageItem;
use App\Http\Resources\Playlist\PlaylistMinimalItem;
use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\Subtitle\SubtitleItem;
use App\Http\Resources\Tag\TagItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\Video;
use App\Models\VideoMeta;
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
            'url' => $this->file_url? : Storage::disk('videos')->url($this->file_path),
            'thumbnail' => $this->thumbnail_url? :$this->thumbnail,
            'thumbnails' => $this->thumbnail_url? getThumbnails($this->thumbnail_url):[],
            'status' => Video::STATUS_TEXT[$this->status]?? null,
            'duration' => $this->duration,
            'user_id' => $this->user_id,
            'view_count' => $this->view_count,
            'category_id' => $this->category_id,
            'created_at' => $this->created_at,
            'published_at' => $this->published_at,
            'watch_time' => $this->watch_time,
            'reason_key' => $this->when($this->reason_key, $this->reason_key),
            'reason_text' => $this->when($this->reason_text, $this->reason_text),
            'channel_id' => $this->channel_id,
            'language_id' => $this->language_id,

            // Custom attributes without query
            'is_published' => $this->is_published,

            // Custom attributes with query
            'rating' => $this->whenAppended('rating'),
            'comment_count' => $this->whenAppended('comment_count'),
            'likes_count' => $this->whenAppended('likes_count'),
            'dislikes_count' => $this->whenAppended('dislikes_count'),
            'reports_count' => $this->whenAppended('reports_count'),
            'is_liked' => $this->whenAppended('is_liked'),
            'is_disliked' => $this->whenAppended('is_disliked'),
            'is_bookmarked' => $this->whenAppended('is_bookmarked'),
            //'layers' => $this->whenAppended('layers'),

            // Relations
            'user' => UserMinimalItem::make($this->whenLoaded('user')),
            'channel' => ChannelResource::make($this->whenLoaded('channel')),
            'category' => CategoryMinimalItem::make($this->whenLoaded('category')),
            'language' => LanguageItem::make($this->whenLoaded('language')),
            'crypto_currencies' => CryptoCurrencyItem::collection($this->whenLoaded('crypto_currencies')),
            'tags' => TagItem::collection($this->whenLoaded('tags')),
            'playlists' => PlaylistMinimalItem::collection($this->whenLoaded('playlists')),
            'subtitles' => SubtitleItem::collection($this->whenLoaded('subtitles')),
            'reports' => ReportMinimalItem::collection($this->whenLoaded('reports')),
            'layers' => VideoMetaResource::make($this->whenLoaded('layers')),
            'layers_draft' => VideoMetaResource::make($this->whenLoaded('layersDraft')),
        ];
    }
}
