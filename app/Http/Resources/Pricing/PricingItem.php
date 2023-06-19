<?php

namespace App\Http\Resources\Pricing;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingItem extends JsonResource
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

        $withPlan = in_array('plan', $include) || $this->relationLoaded('plan');
        $withPaymentMethod = in_array('paymentMethod', $include) || $this->relationLoaded('paymentMethod');

        $plan = ($withPlan)? PlanItem::make($this->plan) : [];
        $paymentMethod = ($withPaymentMethod)? PaymentMethodItem::make($this->paymentMethod) : [];

        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'external_id' => $this->external_id,
            'is_subscription' => (bool) $this->is_subscription,
            'plan' => $this->when($withPlan, $plan),
            'payment_method' => $this->when($withPaymentMethod, $paymentMethod),
            'created_at' => $this->created_at
        ];
    }
}
