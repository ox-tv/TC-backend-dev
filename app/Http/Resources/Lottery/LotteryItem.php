<?php

namespace App\Http\Resources\Lottery;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Transaction\TransactionItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\Earning;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryItem extends JsonResource
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
            'date' => $this->date,
            'created_at' => $this->created_at,
            'winners' => LotteryUserItem::collection($this->lottery_users),
        ];
    }
}
