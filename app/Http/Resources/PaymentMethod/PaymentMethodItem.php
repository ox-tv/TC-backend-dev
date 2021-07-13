<?php

namespace App\Http\Resources\PaymentMethod;

use App\Http\Resources\Pricing\PricingItem;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $include = explode(',', $request->get('include', ''));

        $withPricing = in_array('pricing', $include) || $this->relationLoaded('pricing');

        $pricing = ($withPricing)? PricingItem::collection($this->pricing) : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'pricing' => $this->when($withPricing, $pricing),
            'created_at' => $this->created_at
        ];
    }
}
