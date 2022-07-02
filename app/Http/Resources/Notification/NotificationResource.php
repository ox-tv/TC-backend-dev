<?php

namespace App\Http\Resources\Notification;

use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Comment\CommentResource;
use App\Http\Resources\Report\ReportItem;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'type' => $this->type,
            'payload' => $this->payload,
            'created_at' => $this->created_at,
            'published_at' => $this->published_at,
            'deleted_at' => $this->whenAppended('deleted_at'),
            'read_at' => $this->read_at,

            // Custom attributes without query
            'scope' => $this->scope_text,
            'user_group' => $this->user_group_text,

            // Custom attributes with query

            // Relations
            'from' => UserResource::make($this->whenLoaded('from')),
            'entity' => $this->getEntity(),
            'to' => $this->when(
                $this->resource->user_group == Notification::USER_GROUP_CUSTOM,
                UserResource::collection($this->whenLoaded('users'))
            ),

        ];
    }

    private function getEntity()
    {
        $resources = [
            User::class => UserResource::class,
            Video::class => VideoResource::class,
            Channel::class => ChannelResource::class,
            Category::class => CategoryResource::class,
            Comment::class => CommentResource::class,
            Report::class => ReportItem::class,
        ];

        return !empty($resources[$this->resource->entity_type])?
            $resources[$this->resource->entity_type]::make($this->whenLoaded('entity'))
            : $this->whenLoaded('entity');
    }
}
