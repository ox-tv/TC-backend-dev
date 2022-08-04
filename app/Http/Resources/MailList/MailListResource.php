<?php

namespace App\Http\Resources\MailList;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MailListResource extends JsonResource
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
            'email' => $this->email,
            'location' => $this->location,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,

            // Custom attributes without query

            // Custom attributes with query

            // Relations
        ];
    }
}
