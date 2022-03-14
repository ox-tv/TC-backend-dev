<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Video\VideoResource;
use App\Models\Department;
use App\Models\Message;
use App\Models\UserMeta;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
        $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Application'])->id;
        $publisherRequestDetails = Message::where([
                'user_id' => $this->id,
                'department_id' => $publisherApplicationDepartmentId
            ]
        )->orderBy('created_at', 'asc')->first();


        $publisherRequest = null;
        if (!$this->role_id && $this->meta()->where('key', UserMeta::PUBLISHER_REQUEST_STATUS)->exists()){
            $publisherRequest['status'] = $this->meta()->where('key', UserMeta::PUBLISHER_REQUEST_STATUS)->first()->value?? '';
            $publisherRequest['channel_name'] = $this->meta()->where('key', UserMeta::REQUESTED_CHANNEL_NAME)->first()->value?? '';
        }

        $isEthAddressVisible = $request->is('api/admin/*') || $this->id = auth('api')->id();

        return [
            // Main attributes
            'id' => $this->id,
            'email' => $this->email,
            'eth_address' => $this->when($isEthAddressVisible, $this->eth_address),
            'hero_member_at' => $this->hero_member_at,
            'hero_due_at' => $this->hero_due_at,
            'is_hero' => $this->is_hero,
            'muted_until' => $this->muted_until,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'watch_time' => $this->watch_time,

            // Custom attributes without query
            'is_mute' => $this->is_mute,

            // Custom attributes with query
            'username' => $this->username,
            'avatar' => $this->avatar_url? :$this->avatar,
            'avatar_thumbnails' => $this->avatar_url? getThumbnails($this->avatar_url):[],

            // Relations
            'videos' => VideoResource::collection($this->whenLoaded('main_videos')),




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
            'request_details' => $this->when($withPublisherRequest, $publisherRequestDetails),
            'publisher_request' => $this->when($withPublisherRequest, $publisherRequest),
            'is_conversion' => ($this->created_at >= Carbon::now()->subHours(24) || ($publisherRequestDetails && $publisherRequestDetails->created_at < $this->created_at->addHours(24)))? false : true,
        ];
    }
}
