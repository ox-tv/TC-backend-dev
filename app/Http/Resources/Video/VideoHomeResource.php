<?php

namespace App\Http\Resources\Video;

use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Channel\ChannelHomeResource;
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


class VideoHomeResource extends JsonResource
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
            'url_hash' => $this->url_hash,
            'duration' => $this->duration,
            'view_count' => $this->view_count,
            'published_at' => $this->published_at,

            // Custom attributes without query
            'media_type' => $this->media_type_text,
            'thumbnails' => $this->thumbnails,

            // Custom attributes with query
            'is_bookmarked' => $this->whenAppended('is_bookmarked'),

            // Relations
            'channel' => ChannelHomeResource::make($this->whenLoaded('channel')),
        ];
    }
}
