<?php

namespace App\Http\Resources;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Models\Department;
use App\Models\Message;
use App\Models\UserMeta;
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
        $withFavoriteTags = in_array('favorite_tags', $include) || $this->relationLoaded('favoriteTags');
        $withPublisherRequest = $request->is('api/admin/publisher-requests');


        $channel = ($withChannel)? ChannelMinimalItem::make($this->channel) : null;

        $favoriteTags = ($withFavoriteTags)? $this->favoriteTags : [];

        $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Applications'])->id;

        $isEthAddressVisible = $request->is('api/admin/*') || $this->id = auth('api')->id();

        $publisher_request = null;
        if (!$this->role_id){
            $publisher_request['status'] = $this->meta()->where('key', UserMeta::PUBLISHER_REQUEST_STATUS)->first()->value?? '';
            $publisher_request['channel_name'] = $this->meta()->where('key', UserMeta::REQUESTED_CHANNEL_NAME)->first()->value?? '';
        }


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
            'publisher_request' => $publisher_request,
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

            'loyalty_points' => floatval($this->statistics()->sum('points')),

            'favorite_tags' => $this->when($withFavoriteTags, $favoriteTags),
            'channel' => $this->when($withChannel, $channel),
        ];
    }
}
