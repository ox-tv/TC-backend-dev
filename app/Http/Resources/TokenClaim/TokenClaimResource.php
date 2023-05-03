<?php

namespace App\Http\Resources\TokenClaim;

use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenClaimResource extends JsonResource
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
            'created_at' => $this->created_at,
            'executed_at' => $this->executed_at,
            'amount' => $this->amount,
            'destination' => $this->destination,
            'data' => $this->data,

            // Custom attributes without query
            'status' => $this->status_text,

            // Custom attributes with query

            // Relations
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
