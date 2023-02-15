<?php

namespace App\Http\Resources\CryptoCurrency;

use App\Http\Resources\CryptoCampaign\CryptoCampaignResource;
use App\Http\Resources\Video\VideoResource;
use Illuminate\Http\Resources\Json\JsonResource;


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
        return [
            // Main attributes
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'order' => $this->order,
            'symbol' => strtoupper($this->symbol),
            'ratio' => $this->prices,
            'metadata' => $this->metadata,

            // Custom attributes without query
            'status' => $this->status_text,
            'thumbnails' => $this->thumbnails,

            // Custom attributes with query
            'is_favorite' => $this->whenAppended('is_favorite'),

            // Relations
            'videos' => VideoResource::collection($this->whenLoaded('videos')),
            'campaigns' => CryptoCampaignResource::collection($this->whenLoaded('cryptoCampaigns')),
            'active_campaigns' => CryptoCampaignResource::collection($this->whenLoaded('activeCryptoCampaigns')),
        ];
    }
}
