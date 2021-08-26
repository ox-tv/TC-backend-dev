<?php

namespace App\Http\Resources\Language;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageItem extends JsonResource
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
            'code' => $this->code,
            'display_name' => $this->display_name,
        ];
    }
}
