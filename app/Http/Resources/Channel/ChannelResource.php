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
            'cover' => $this->cover_url? :$this->cover,
            'cover_thumbnails' => $this->cover_url? getThumbnails($this->cover_url):[],
            'avatar' => $this->avatar_url? :$this->avatar,
            'avatar_thumbnails' => $this->avatar_url? getThumbnails($this->avatar_url):[],
            "instagram" => $this->instagram,
            "facebook" => $this->facebook,
            "twitter" => $this->twitter,
            "website" => $this->website,
            "slogan" => $this->slogan,
            "user_id" => $this->user_id,
            'points' => $this->points,
            "import_request_status" => Channel::IMPORT_STATUS_TEXT[$this->import_request_status]?? null,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,

            // Custom attributes without query
            'status' => $this->status_text,

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
