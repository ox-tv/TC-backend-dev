<?php

namespace App\Http\Resources;

use App\Models\Department;
use App\Models\Message;
use Illuminate\Http\Resources\Json\JsonResource;

class UserItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withPublisherRequest = $request->is('api/admin/publisher-requests');

        $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Applications'])->id;

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
            'liked_videos_count' => $this->likedVideos()->count(),
            'disliked_videos_count' => $this->dislikedVideos()->count(),
            'comments_count' => $this->comments()->count(),
            'subscribed_channels_count' => $this->subscribedChannels()->count(),
            'watch_time' => $this->watch_time,
            'role' => $this->role_name,
            'referral_code' => $this->referral_code,
            'request_details' => $this->when(
                $withPublisherRequest,
                Message::where([
                    'user_id' => $this->id,
                    'department_id' => $publisherApplicationDepartmentId
                    ]
                )->orderBy('created_at', 'desc')->first()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'loyalty_points' => 0,
        ];
    }
}
