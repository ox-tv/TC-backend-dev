<?php

namespace App\Http\Resources\TokenPoint;

use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenPointResource extends JsonResource
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
            'date' => $this->date,
            'user_id' => $this->user_id,
            'amount' => $this->amount,

            // Custom attributes without query
            'type' => $this->type_text,

            // Custom attributes with query

            // Relations
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
