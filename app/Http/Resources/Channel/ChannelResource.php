<?php

namespace App\Http\Resources\Channel;

use App\Http\Resources\User\UserResource;
use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'url_hash' => $this->url_hash,
            'cover' => $this->cover,
            'avatar' => $this->avatar,
            "instagram" => $this->instagram,
            "facebook" => $this->facebook,
            "twitter" => $this->twitter,
            "website" => $this->website,
            "telegram" => $this->telegram,
            "reddit" => $this->reddit,
            "linkedin" => $this->linkedin,
            "tiktok" => $this->tiktok,
            "slogan" => $this->slogan,
            "user_id" => $this->user_id,
            'points' => $this->points,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "youtube_last_scraped_at" => $this->youtube_last_scraped_at,
            "monetization_qualified_at" => $this->whenAppended('monetization_qualified_at'),

            // Custom attributes without query
            'status' => $this->status_text,
            "import_request_status" => $this->import_request_status_text,
            'avatar_thumbnails' => $this->avatar_thumbnails,
            'cover_thumbnails' => $this->cover_thumbnails,

            // Custom attributes with query
            'uploads_count' => $this->whenAppended('uploads_count'),
            'total_views' => $this->whenAppended('total_views'),
            'watch_time' => $this->whenAppended('watch_time'),
            'total_likes' => $this->whenAppended('total_likes'),
            'total_dislikes' => $this->whenAppended('total_dislikes'),
            'comments_count' => $this->whenAppended('total_comments'),
            'is_subscribed' => $this->whenAppended('is_subscribed'),
            'subscribers_count' => $this->whenAppended('subscribers_count'),
            'hero_subscribers_count' => $this->whenAppended('hero_subscribers_count'),

            // Relations
            'owner' => UserResource::make($this->whenLoaded('owner')),
        ];
    }
}
