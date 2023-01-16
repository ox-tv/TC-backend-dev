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
            'likes_hero' => ($temp = $this->likes_hero) > 0? $temp : 0,
            'likes_non_hero' => ($temp = $this->likes_non_hero) > 0? $temp : 0,
            'likes_total' => ($temp = $this->likes_total) > 0? $temp : 0,
            'dislikes_hero' => ($temp = $this->dislikes_hero) > 0? $temp : 0,
            'dislikes_non_hero' => ($temp = $this->dislikes_non_hero) > 0? $temp : 0,
            'dislikes_total' => ($temp = $this->dislikes_total) > 0? $temp : 0,
            'comments_hero' => $this->comments_hero,
            'comments_non_hero' => $this->comments_non_hero,
            'comments_total' => $this->comments_total,
            'watch_time_hero' => $this->watch_time_hero,
            'watch_time_non_hero' => $this->watch_time_non_hero,
            'watch_time_total' => $this->watch_time_total,
            'points' => 0, // TODO: Remove this row after a while
            'video' => $this->when($withVideo, $video),
        ];
    }
}
