<?php

namespace App\Http\Resources\Ad;

use App\Http\Resources\User\UserResource;
use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class AdDiscountResource extends JsonResource
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
            'tier' => $this->tier,
            'amount' => $this->amount,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,

            'type' => $this->type_text,
        ];
    }
}
