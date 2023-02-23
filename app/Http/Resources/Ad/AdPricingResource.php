<?php

namespace App\Http\Resources\Ad;

use App\Http\Resources\User\UserResource;
use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class AdPricingResource extends JsonResource
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
        ];
    }
}
