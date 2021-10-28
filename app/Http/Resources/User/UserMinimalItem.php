<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Models\Department;
use App\Models\Message;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMinimalItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withChannel = $this->relationLoaded('channel');
        $channel = ($withChannel)? ChannelMinimalItem::make($this->channel) : [];

        $isEthAddressVisible = $request->is('api/admin/*') || $this->id = auth('api')->id();

        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'eth_address' => $this->when($isEthAddressVisible, $this->eth_address),
            'hero_member_at' => $this->hero_member_at,
            'hero_due_at' => $this->hero_due_at,
            'is_hero' => $this->is_hero,
            'is_mute' => $this->is_mute,
            'muted_until' => $this->muted_until,
            'role_id' => $this->role_id,
            'created_at' => $this->created_at,
            'watch_time' => $this->watch_time,
            'channel' => $this->when($withChannel, $channel),
            'referral_code' => $this->referral_code,

            'loyalty_points' => 0,
        ];
    }
}
