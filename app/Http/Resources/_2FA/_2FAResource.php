<?php

namespace App\Http\Resources\_2FA;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class _2FAResource extends JsonResource
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
            'ip' => $this->ip,
            'app_enable' => (bool)$this->app_status,
            'email_enable' => (bool)$this->email_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Custom attributes without query
            'app_type' => $this->app_type_text,

            // Custom attributes with query

            // Relations
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
