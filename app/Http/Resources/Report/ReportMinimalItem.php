<?php

namespace App\Http\Resources\Report;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Video\VideoItem;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;


class ReportMinimalItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $type = "";

        if($this->reportable_type == Video::class){
            $type = "video";
        }
        if($this->reportable_type == Channel::class){
            $type = "channel";
        }
        if($this->reportable_type == Comment::class){
            $type = "comment";
        }
        return [
            'id' => $this->id,
            'user' => $this->user,
            'reported_type' => $type,
            'reported_id' => $this->reportable_id,
            'reported_user_id' => $this->reported_user_id,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}
