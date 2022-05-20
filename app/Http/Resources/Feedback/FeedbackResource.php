<?php

namespace App\Http\Resources\Feedback;

use App\Http\Resources\User\UserResource;
use App\Models\Channel;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
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
            'location' => $this->location,
            'value' => $this->value,
            'text' => $this->text,
            "user_id" => $this->user_id,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,

            // Custom attributes without query
            'type' => $this->type_text,

            // Custom attributes with query

            // Relations
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
