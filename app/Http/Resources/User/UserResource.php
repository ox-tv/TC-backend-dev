<?php

namespace App\Http\Resources\User;

use App\Http\Resources\_2FA\_2FAResource;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\CryptoCurrency\CryptoCurrencyResource;
use App\Http\Resources\PaymentDetails\PaymentDetailsResource;
use App\Http\Resources\Tag\TagResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\PaymentDetails;
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
        return [
            // Main attributes
            'id' => $this->id,
            'email' => $this->email,
            'eth_address' => $this->whenAppended('eth_address'),
            'wallet' => $this->whenAppended('auth_wallet'),
            'hero_member_at' => $this->hero_member_at,
            'hero_due_at' => $this->hero_due_at,
            'muted_until' => $this->muted_until,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_actived_at' => $this->last_actived_at,
            'email_verified_at' => $this->email_verified_at,
            'identity_verified_at' => $this->identity_verified_at,
            'watch_time' => $this->watch_time,
            'referral_code' => $this->referral_code,

            // Custom attributes without query
            'is_hero' => $this->is_hero,
            'is_mute' => $this->is_mute,
            'avatar_thumbnails' => $this->avatar_thumbnails,
            'idenfy_name_data' => $this->idenfy_name_data,
            'deletion_feedback' => $this->whenAppended('deletion_feedback'),
            'deleted_at' => $this->whenAppended('deleted_at'),

            // Custom attributes with query
            'username' => $this->username,
            'avatar' => $this->avatar,
            'role' => $this->whenAppended('role_name'),
            'is_publisher' => $this->whenAppended('is_publisher'),
            'liked_videos_count' => $this->whenAppended('liked_videos_count'),
            'disliked_videos_count' => $this->whenAppended('disliked_videos_count'),
            'bookmarked_videos_count' => $this->whenAppended('bookmarked_videos_count'),
            'comments_count' => $this->whenAppended('comments_count'),
            'subscribed_channels_count' => $this->whenAppended('subscribed_channels_count'),
            'publisher_request' => $this->whenAppended('publisher_request'),
            'request_details' => $this->whenAppended('publisher_request_details'),
            'is_conversion' => $this->whenAppended('is_conversion'),
            'loyalty_points' => $this->whenAppended('loyalty_points'),
            'is_hero_membership_auto_renewal' => $this->whenAppended('isHeroMembershipAutoRenewal'),
            'channel_auto_import_is_active' => $this->whenAppended('channelAutoImportIsActive'),
            'referrals_count' => $this->whenAppended('referrals_count'),
            'favorite_crypto_currencies_count' => $this->whenAppended('favoriteCryptoCurrenciesCount'),

            // Relations
            'channel' => ChannelResource::make($this->whenLoaded('channel')),
            'subscribed_channels' => ChannelResource::collection($this->whenLoaded('subscribedChannels')),
            'referrer' => UserResource::make($this->whenLoaded('referrer')),
            'referrals' => UserResource::collection($this->whenLoaded('referrals')),
            'meta' => $this->whenLoaded('meta'),
            'favorite_tags' => TagResource::collection($this->whenLoaded('favoriteTags')),
            'favorite_crypto_currencies' => CryptoCurrencyResource::collection($this->whenLoaded('favoriteCryptoCurrencies')),
            'bookmark_videos' => VideoResource::collection($this->whenLoaded('bookmarkVideos')),
            'verified_payment_details' => PaymentDetailsResource::make($this->whenLoaded('verifiedPaymentDetails')),
            'last_payment_details' => PaymentDetailsResource::make($this->whenLoaded('lastPaymentDetails')),
        ];
    }
}
