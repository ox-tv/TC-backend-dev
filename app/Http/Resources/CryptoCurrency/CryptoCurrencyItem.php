<?php

namespace App\Http\Resources\CryptoCurrency;

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
use App\Models\CryptoCurrency;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class CryptoCurrencyItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $image_small = '';

        if($this->coinmarketcap_id){
            $image_small = "https://s2.coinmarketcap.com/static/img/coins/64x64/{$this->coinmarketcap_id}.png";
        }

        $is_favorite = false;

        if(is_null($this->is_favorite)
            && auth('api')->check()
            && auth('api')->user()->favoriteCryptoCurrencies()->where('crypto_currency_id', $this->id)->exists()){
                $is_favorite = true;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'symbol' => $this->symbol,
            'status' => $this->status ? CryptoCurrency::STATUS_TEXT[$this->status] : null,
            'thumbnails' => [
                'small' => $image_small
            ],
            'ratio' => $this->when(!empty($this->prices), $this->prices),
            'is_favorite' => $this->is_favorite?? $is_favorite,
            'metadata' => $this->metadata,
        ];
    }
}
