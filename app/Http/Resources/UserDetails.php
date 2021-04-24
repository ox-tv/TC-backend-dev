<?php

namespace App\Http\Resources;

use App\Models\Department;
use App\Models\Message;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetails extends JsonResource
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
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'eth_address' => $this->eth_address,
            'hero_member_at' => $this->hero_member_at,
            'hero_due_at' => $this->hero_due_at,
            'is_hero' => $this->is_hero,
            'is_mute' => $this->is_mute,
            'likes_count' => rand(10,99),
            'dislikes_count' => rand(10,99),
            'watch_hours' => rand(10,99),
            'subscription_count' => $this->subscribedChannels()->count(),
            'role' => $this->role_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
