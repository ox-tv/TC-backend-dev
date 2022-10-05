<?php

namespace App\Http\Resources\Earning;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Transaction\TransactionItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\Earning;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningItem extends JsonResource
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
            'amount' => $this->amount,
            'currency' => $this->currency,
            'client_information' => $this->client_informations,
            'status' => Earning::STATUS_TEXT[$this->status]?? null,
            'created_at' => $this->created_at,
            'date' => $this->date,
            'transaction' => $this->when($withTransaction, $transaction),
            'user' => $this->when($withUser, $user),
        ];
    }
}
