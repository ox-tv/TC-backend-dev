<?php

namespace App\Http\Resources\Plan;

use App\Http\Resources\Pricing\PricingItem;
use App\Models\Plan;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanItem extends JsonResource
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
            'interval' => $this->interval,
            'status' => Plan::STATUS_TEXT[$this->status],
            'thumbnail' => $this->thumbnail_url? :$this->thumbnail,
            'thumbnails' => $this->thumbnail_url? getThumbnails($this->thumbnail_url):[],
            'is_popular' => (bool) $this->is_popular,
            'pricing' => $this->when($withPricing, $pricing),
            'created_at' => $this->created_at
        ];
    }
}
