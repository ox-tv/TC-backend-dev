<?php

namespace App\Http\Resources\CryptoCampaign;

use App\Http\Resources\CryptoCurrency\CryptoCurrencyResource;
use App\Http\Resources\User\UserResource;
use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class CryptoCampaignResource extends JsonResource
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
            'name' => $this->name,
            'headline' => $this->headline,
            'description' => $this->description,
            "exchange_name" => $this->exchange_name,
            "exchange_main_url" => $this->exchange_main_url,
            "exchange_referral_url" => $this->exchange_referral_url,
            "thumbnail" => $this->thumbnail,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,

            // Custom attributes without query
            'status' => $this->status_text,
            'total_clicks' => 0,
            'crypto_currencies_count' => 0,

            // Custom attributes with query

            // Relations
            'crypto_currencies' => CryptoCurrencyResource::collection($this->whenLoaded('crypto_currencies')),
        ];
    }
}
