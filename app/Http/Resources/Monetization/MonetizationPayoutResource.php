<?php

namespace App\Http\Resources\Monetization;

use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MonetizationPayoutResource extends JsonResource
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
            'channel_id' => $this->channel_id,
            'monetization_id' => $this->monetization_id,
            'wallet_address' => $this->wallet_address,
            'payment_details' => $this->payment_details,
            'metrics' => $this->metrics,
            'amount' => $this->amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Custom attributes without query
            'status' => $this->status_text,

            // Relations
            'channel' => ChannelResource::make($this->whenLoaded('channel')),
            'monetization' => $this->whenLoaded('monetization'),
        ];
    }
}
