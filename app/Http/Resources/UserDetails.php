<?php

namespace App\Http\Resources;

use App\Models\Department;
use App\Models\Message;
use App\Models\UserMeta;
use App\Models\UserVideo;
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
        $isEthAddressVisible = $request->is('api/admin/*') || $this->id = auth('api')->id();

        $publisher_request = null;
        if (!$this->role_id && $this->meta()->where('key', UserMeta::PUBLISHER_REQUEST_STATUS)->exists()){
            $publisher_request['status'] = $this->meta()->where('key', UserMeta::PUBLISHER_REQUEST_STATUS)->first()->value?? '';
            $publisher_request['channel_name'] = $this->meta()->where('key', UserMeta::REQUESTED_CHANNEL_NAME)->first()->value?? '';
        }

        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar_url? :$this->avatar,
            'avatar_thumbnails' => $this->avatar_url? getThumbnails($this->avatar_url):[],
            'eth_address' => $this->when($isEthAddressVisible, $this->eth_address),
            'hero_member_at' => $this->hero_member_at,
            'hero_due_at' => $this->hero_due_at,
            'is_hero' => $this->is_hero,
            'is_mute' => $this->is_mute,
            'muted_until' => $this->muted_until,
            'likes_count' => UserVideo::where("user_id", $this->id)->where("relation", UserVideo::LIKED_RELATION)->count(),
            'dislikes_count' => UserVideo::where("user_id", $this->id)->where("relation", UserVideo::DISLIKED_RELATION)->count(),
            'watch_time' => $this->watch_time,
            'subscription_count' => $this->subscribedChannels()->count(),
            'comments_count' => $this->comments()->count(),
            'role' => $this->role_name,
            'referral_code' => $this->referral_code,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'publisher_request' => $publisher_request,

            'loyalty_points' => intval($this->statistics()->sum('points')),
        ];
    }
}
