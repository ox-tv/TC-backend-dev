<?php

namespace App\Http\Resources\CryptoCurrency;

use App\Models\CryptoCurrency;
use Illuminate\Http\Resources\Json\JsonResource;


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
        $prices = (!empty($this->prices))? $this->prices : [];

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
            'ratio' => $prices,
            'is_favorite' => $this->is_favorite?? $is_favorite,
            'metadata' => $this->metadata,
        ];
    }
}
