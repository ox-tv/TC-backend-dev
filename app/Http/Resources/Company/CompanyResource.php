<?php

namespace App\Http\Resources\Company;

use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'avatar_url' => $this->avatar_url,
            'vat_number' => $this->vat_number,
            'vat_rate' => $this->vat_rate,

            'street_address' => $this->street_address,
            'street_no' => $this->street_no,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,

            'contact_person_name' => $this->contact_person_name,
            'contact_person_email' => $this->contact_person_email,
            'contact_person_phone' => $this->contact_person_phone,
            'invocing_questions_email' => $this->invocing_questions_email,

            'created_at' => $this->created_at,

            // Custom attributes without query
            'avatar_thumbnails' => $this->avatar_thumbnails,

            // Custom attributes with query

            // Relations

        ];
    }
}
