<?php

namespace App\Http\Resources;

use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelSummaryItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withOwner = in_array('owner', explode(',', $request->get('include', '')));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subscribers_count' => $this->subscribers->count(),
            'uploads_count' => $this->uploads_count,
            'total_views' => $this->total_views,
            'total_likes' => $this->total_likes,
            'total_dislikes' => $this->total_dislikes,
            'comments_count' => $this->total_comments,
            'points' => $this->points,
            // TODO:: calculate real watch hours
            'watch_hours' => rand(1,99),
            'hero_subscribers_count' => $this->heroSubscribers->count(),
            "owner" => $this->when($withOwner, UserItem::make($this->owner)),
            'url_hash' => $this->url_hash,
            'avatar' => $this->avatar,
            "slogan" => $this->slogan,
            "status" => $this->status ? Channel::STATUS_TEXT[$this->status] : null,
            "import_request_status" => Channel::IMPORT_STATUS_TEXT[$this->import_request_status]?? null,
            'is_subscribed' => auth('api')->check() ? ($this->subscribers()->find(auth('api')->user()->id) ? true : false) : false,
        ];
    }
}
