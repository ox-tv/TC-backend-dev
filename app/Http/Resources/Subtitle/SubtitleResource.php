<?php

namespace App\Http\Resources\Subtitle;

use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Language\LanguageResource;
use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SubtitleResource extends JsonResource
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
            'url' => Storage::disk('public')->url($this->file_path),

            // Custom attributes without query

            // Custom attributes with query

            // Relations
            'language' => LanguageResource::make($this->whenLoaded('language')),
            'video' => VideoResource::make($this->whenLoaded('video')),
        ];
    }
}
