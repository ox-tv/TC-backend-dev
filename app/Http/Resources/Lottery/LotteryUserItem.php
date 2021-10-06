<?php

namespace App\Http\Resources\Lottery;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Transaction\TransactionItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\Earning;
use App\Models\LotteryUser;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryUserItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $include = explode(',', $request->get('include', ''));

        $withTransaction = in_array('transaction', $include) || $this->relationLoaded('transaction');
        $transaction = ($withTransaction)? TransactionItem::make($this->transaction) : [];

        $withUser = in_array('user', $include) || $this->relationLoaded('user');
        $user = ($withUser)? UserMinimalItem::make($this->user) : [];

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'lottery_id' => $this->lottery_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => LotteryUser::STATUS_TEXT[$this->status]?? null,
            'transaction' => $this->when($withTransaction, $transaction),
            'user' => $this->when($withUser, $user),
        ];
    }
}
