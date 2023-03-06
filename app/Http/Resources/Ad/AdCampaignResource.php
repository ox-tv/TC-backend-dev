<?php

namespace App\Http\Resources\Ad;

use App\Http\Resources\Company\CompanyResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdCampaignResource extends JsonResource
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
            "name" => $this->name,
            'data' => $this->data,

            // Custom attributes without query
            'status' => $this->status_text,

            // Relations
            'company' => CompanyResource::make($this->whenLoaded('company')),
            'slots' => AdSlotResource::collection($this->whenLoaded('slots')),
        ];
    }
}
