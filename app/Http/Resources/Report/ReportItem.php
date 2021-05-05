<?php

namespace App\Http\Resources\Report;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\CommentItem;
use App\Http\Resources\CommentSummaryItem;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;


class ReportItem extends JsonResource
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
            $reported_item = VideoMinimalItem::make($this->reportable);
            $type = "video";
        }
        if($this->reportable_type == Channel::class){
            $reported_item = ChannelMinimalItem::make($this->reportable);
            $type = "channel";
        }
        if($this->reportable_type == Comment::class){
            $reported_item = CommentSummaryItem::make($this->reportable);
            $type = "comment";
        }

        return [
            'id' => $this->id,
            'user' => $this->user,
            'reported_type' => $type,
            'reported_item' => $reported_item,
            'reported_user' => $this->reported_user,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}
