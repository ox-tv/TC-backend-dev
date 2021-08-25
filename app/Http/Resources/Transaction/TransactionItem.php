<?php

namespace App\Http\Resources\Transaction;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItem extends JsonResource
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

        $withPaymentMethod = in_array('payment_method', $include) || $this->relationLoaded('payment_method');
        $payment_method = ($withPaymentMethod)? PaymentMethodItem::make($this->payment_method) : [];

        return [
            'id' => $this->id,
            'type' => Transaction::TYPE_TEXT[$this->type]?? null,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => Transaction::STATUS_TEXT[$this->status]?? null,
            'created_at' => $this->created_at,
            'completed_at' => $this->completed_at,
            'reference' => $this->reference,
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => $this->when($withPaymentMethod, $payment_method),
        ];
    }
}
