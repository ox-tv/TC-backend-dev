<?php

namespace App\Http\Resources\Subtitle;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SubtitleItem extends JsonResource
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
            'id' => $this->id,
            'url' => Storage::disk('public')->url($this->file_path),
            'language' => $this->language,
            'video_id' => $this->video_id,
        ];
    }
}
