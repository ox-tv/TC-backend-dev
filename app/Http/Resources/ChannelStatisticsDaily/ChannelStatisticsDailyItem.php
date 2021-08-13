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
            'subscribers_hero' => $this->subscribers_hero,
            'subscribers_non_hero' => $this->subscribers_non_hero,
            'subscribers_total' => $this->subscribers_total,
            'unsubscribers_hero' => $this->unsubscribers_hero,
            'unsubscribers_non_hero' => $this->unsubscribers_non_hero,
            'unsubscribers_total' => $this->unsubscribers_total,
            'upload_videos_total' => $this->upload_videos_total,
            'channel' => $this->when($withChannel, $channel),
        ];
    }
}
