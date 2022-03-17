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


class VideoMetaResource extends JsonResource
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
            'key' => $this->key,
            'value' => $this->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Custom attributes without query

            // Custom attributes with query

            // Relations
            'video' => VideoResource::make($this->whenLoaded('video')),
        ];
    }
}
