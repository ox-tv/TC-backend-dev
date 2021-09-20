<?php

namespace App\Http\Resources\PricingUser;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use App\Http\Resources\Transaction\TransactionItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\PricingUser;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingUserItem extends JsonResource
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
        $withUser = in_array('user', $include) || $this->relationLoaded('user');

        $transaction = ($withTransaction)? TransactionItem::make($this->transaction) : [];
        $user = ($withUser)? UserMinimalItem::make($this->user) : [];

        return [
            'id' => $this->id,
            'status' => PricingUser::STATUS_TEXT[$this->status]?? null,
            'payment_method' => $this->metadata['payment_method']??null,
            'pricing' => $this->metadata['pricing']??null,
            'plan' => $this->metadata['plan']??null,
            'transaction' => $this->when($withTransaction, $transaction),
            'user' => $this->when($withUser, $user),
            'created_at' => $this->created_at
        ];
    }
}
