<?php

namespace App\Http\Resources\VideoStatisticsDaily;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use App\Http\Resources\Video\VideoMinimalItem;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoStatisticsDailyItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withVideo = $this->relationLoaded('video');

        $video = ($withVideo)? VideoMinimalItem::make($this->video) : [];

        return [
            'id' => $this->id,
            'date' => $this->date,
            'video_id' => $this->video_id,
            'channel_id' => $this->channel_id,
            'views_hero' => $this->views_hero,
            'views_non_hero' => $this->views_non_hero,
            'views_total' => $this->views_total,
            'likes_hero' => $this->likes_hero,
            'likes_non_hero' => $this->likes_non_hero,
            'likes_total' => $this->likes_total,
            'dislikes_hero' => $this->dislikes_hero,
            'dislikes_non_hero' => $this->dislikes_non_hero,
            'dislikes_total' => $this->dislikes_total,
            'points' => $this->points,
            'video' => $this->when($withVideo, $video),
        ];
    }
}
