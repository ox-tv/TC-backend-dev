<?php

namespace App\Http\Resources\PaymentDetails;

use App\Http\Resources\Pricing\PricingItem;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentDetailsResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            // Main attributes
            'id' => $this->id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'last_status_at' => $this->last_status_at,
            'code_sent_at' => $this->code_sent_at,
            'proof_code' => $this->whenAppended('proof_code'),
            'is_archive' => $this->whenAppended('is_archive'),

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'street_address' => $this->street_address,
            'street_number' => $this->street_number,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,
            'company_name' => $this->company_name,
            'vat_number' => $this->vat_number,
            'eth_address' => $this->whenAppended('eth_address'),

            // Custom attributes without query
            'status' => $this->status_text,

            // Custom attributes with query

            // Relations
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
