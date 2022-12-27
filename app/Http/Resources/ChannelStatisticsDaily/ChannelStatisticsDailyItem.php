<?php

namespace App\Http\Resources\ChannelStatisticsDaily;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Plan\PlanItem;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelStatisticsDailyItem extends JsonResource
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

        return [
            'id' => $this->id,
            'date' => $this->date,
            'channel_id' => $this->channel_id,
            'subscribers_hero' => ($temp = $this->subscribers_hero) > 0? $temp : 0,
            'subscribers_non_hero' => ($temp = $this->subscribers_non_hero) > 0? $temp : 0,
            'subscribers_total' => ($temp = $this->subscribers_total) > 0? $temp : 0,
            'unsubscribers_hero' => ($temp = $this->unsubscribers_hero) > 0? $temp : 0,
            'unsubscribers_non_hero' => ($temp = $this->unsubscribers_non_hero) > 0? $temp : 0,
            'unsubscribers_total' => ($temp = $this->unsubscribers_total) > 0? $temp : 0,
            'upload_videos_total' => $this->upload_videos_total,
            'published_videos' => $this->published_videos,
            'unpublished_videos' => $this->unpublished_videos,
            'channel' => $this->when($withChannel, $channel),
        ];
    }
}
