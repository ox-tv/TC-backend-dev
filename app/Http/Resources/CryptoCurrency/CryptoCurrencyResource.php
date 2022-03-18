<?php

namespace App\Http\Resources\CryptoCurrency;

use App\Http\Resources\Category\CategoryCollection;
use App\Http\Resources\Category\CategoryItem;
use App\Http\Resources\Category\CategoryMinimalCollection;
use App\Http\Resources\Category\CategoryMinimalItem;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Playlist\PlaylistMinimalCollection;
use App\Http\Resources\Playlist\PlaylistMinimalItem;
use App\Http\Resources\Report\ReportItem;
use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\VideoCommentCollection;
use App\Http\Resources\VideoSummaryCollection;
use App\Models\CryptoCurrency;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class CryptoCurrencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $prices = (!empty($this->prices))? $this->prices : [];

        $image_small = '';

        if($this->coinmarketcap_id){
            $image_small = "https://s2.coinmarketcap.com/static/img/coins/64x64/{$this->coinmarketcap_id}.png";
        }



        return [
            // Main attributes
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'symbol' => $this->symbol,
            'metadata' => $this->metadata,

            // Custom attributes without query
            'status' => $this->status_text,

            // Custom attributes with query
            'is_favorite' => $this->whenAppended('is_favorite'),

            // Relations
            //'owner' => UserResource::make($this->whenLoaded('owner')),
            //'channel' => ChannelResource::make($this->whenLoaded('channel')),


            'thumbnails' => [
                'small' => $image_small
            ],
            'ratio' => $prices,
        ];
    }
}
