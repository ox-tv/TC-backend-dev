<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Channel\ChannelMinimalItem;
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
        $include = explode(',', $request->get('include', ''));

        $withChannel = in_array('channel', $include) || $this->relationLoaded('channel');
        $withRole = in_array('role', $include) || $this->relationLoaded('role');
        $withBookmarkVideos = in_array('bookmarkVideos', $include) || $this->relationLoaded('bookmarkVideos');
        $withReferrer = in_array('referrer', $include) || $this->relationLoaded('referrer');
        $withFavoriteTags = in_array('favorite_tags', $include) || $this->relationLoaded('favoriteTags');

        $channel = ($withChannel)? ChannelMinimalItem::make($this->channel) : [];
        $role = ($withRole)? $this->role : [];
        $bookmarkVideos = ($withBookmarkVideos)? ChannelMinimalItem::make($this->role) : [];
        $referrer = ($withReferrer)? UserMinimalItem::make($this->referrer) : [];
        $favoriteTags = ($withFavoriteTags)? $this->favoriteTags : [];


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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'watch_time' => $this->watch_time,

            'loyalty_points' => floatval($this->statistics()->sum('points')),

            //'role' => $this->when($withRole, $role),
            'role' => $this->role_name,
            'channel' => $this->when($withChannel, $channel),
            'bookmark_videos' => $this->when($withBookmarkVideos, $bookmarkVideos),

            'referral_code' => $this->referral_code,
            'referrer' => $this->when($withReferrer, $referrer),

            'favorite_tags' => $this->when($withFavoriteTags, $favoriteTags),

            'liked_videos_count' => $this->likedVideos()->count(),
            'disliked_videos_count' => $this->dislikedVideos()->count(),
            'comments_count' => $this->comments()->count(),
            'subscribed_channels_count' => $this->subscribedChannels()->count(),
            'request_details' => $this->when(
                $withPublisherRequest,
                Message::where([
                    'user_id' => $this->id,
                    'department_id' => $publisherApplicationDepartmentId
                    ]
                )->orderBy('created_at', 'asc')->first()
            ),
        ];
    }
}
