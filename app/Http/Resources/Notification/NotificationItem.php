<?php

namespace App\Http\Resources\Notification;

use App\Http\Resources\Category\CategoryMinimalItem;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Comment\CommentItem;
use App\Http\Resources\Department\DepartmentItem;
use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $withEntity = $this->relationLoaded('entity');
        $withFrom = $this->relationLoaded('from');

        if ($this->entity_type == User::class){
            $entity = ($withEntity)? UserMinimalItem::make($this->entity) : null;
        }elseif ($this->entity_type == Video::class){
            $entity = ($withEntity)? VideoMinimalItem::make($this->entity) : null;
        }elseif ($this->entity_type == Channel::class){
            $entity = ($withEntity)? ChannelMinimalItem::make($this->entity) : null;
        }elseif ($this->entity_type == Category::class){
            $entity = ($withEntity)? CategoryMinimalItem::make($this->entity) : null;
        }elseif ($this->entity_type == Comment::class){
            $entity = ($withEntity)? CommentItem::make($this->entity) : null;
        }elseif ($this->entity_type == Report::class){
            $entity = ($withEntity)? ReportMinimalItem::make($this->entity) : null;
        }else{
            $entity = ($withEntity)? $this->entity : null;
        }

        $from = ($withFrom)? UserMinimalItem::make($this->from) : null;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'payload' => $this->payload,
            'scope' => Notification::SCOPE_TEXT[$this->scope],
            'user_group' => Notification::USER_GROUP_TEXT[$this->user_group]?? null,
            'from' => $this->when($withFrom, $from),
            'created_at' => $this->created_at,
            'read_at' => $this->read_at,
            'entity' => $this->when($withEntity, $entity),
        ];
    }
}
