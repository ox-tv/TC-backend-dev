<?php

namespace App\Http\Resources\Ad;

use Illuminate\Http\Resources\Json\JsonResource;

class AdSlotResource extends JsonResource
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
            "date" => $this->date,
            'tier' => $this->tier,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'ad_campaign_id' => $this->ad_campaign_id,

            // Relations
            'campaign' => AdCampaignResource::make($this->whenLoaded('campaign')),
        ];
    }
}
