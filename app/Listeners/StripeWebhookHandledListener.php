<?php

namespace App\Listeners;

use App\Events\User\BuyingHeroMemberShipCompleted;
use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use App\Http\Resources\Pricing\PricingItem;
use App\Models\Pricing;
use App\Models\PricingUser;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class StripeWebhookHandledListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(WebhookReceived $event)
    {
        if ($event->payload['type'] === 'customer.subscription.created') {
            $this->handleCustomerSubscriptionCreated($event->payload);
        }

        if ($event->payload['type'] === 'customer.subscription.updated') {
            $this->handleCustomerSubscriptionUpdated($event->payload);
        }

        if ($event->payload['type'] === 'customer.subscription.deleted') {
            // Handle the incoming event...
        }

        return true;
    }

    protected function handleCustomerSubscriptionCreated($payload)
    {
        if (empty($payload['data']['object']['customer'])){
            return false;
        }

        $user = Cashier::findBillable($payload['data']['object']['customer']);

        if (!$user){
            return false;
        }

        $userBeforeUpdate = clone $user;

        $subId = $payload['data']['object']['id'];
        $pricingUser = PricingUser::where('metadata->subscription_id', $subId)->first();

        if ($pricingUser){
            return false;
        }


        $planId = $payload['data']['object']['plan']['id'];
        $pricing = Pricing::where('external_id', $planId)->first();
        if (!$pricing){
            return false;
        }

        $pricingUser = new PricingUser();

        DB::transaction(function () use ($subId, $pricing, $user, $pricingUser){

            $plan = $pricing->plan()->first();
            $paymentMethod = $pricing->paymentMethod()->first();

            $transaction = new Transaction();
            $transaction->type = Transaction::TYPE_DEPOSIT;
            $transaction->status = Transaction::STATUS_COMPLETED;
            $transaction->payment_method_id = $paymentMethod->id;
            $transaction->amount = $pricing->amount;
            $transaction->save();

            $pricingUser->user_id = $user->id;
            $pricingUser->pricing_id = $pricing->id;
            $pricingUser->status = PricingUser::STATUS_COMPLETED;
            $pricingUser->metadata = [
                'pricing' => PricingItem::make($pricing),
                'plan' => PlanItem::make($plan),
                'payment_method' => PaymentMethodItem::make($paymentMethod),
                'subscription_id' => $subId,
            ];
            $pricingUser->transaction_id = $transaction->id;
            $pricingUser->save();


            if ($user->hero_due_at && $user->hero_due_at > Carbon::now()){
                $user->hero_due_at = $user->hero_due_at->addDays($plan->interval);
            }else{
                $user->hero_due_at = Carbon::now()->addDays($plan->interval);
            }
            $user->save();
        });

        event(new BuyingHeroMemberShipCompleted($userBeforeUpdate, $pricingUser));

        return true;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $this->handleCustomerSubscriptionCreated($payload);
    }

    protected function handleCustomerSubscriptionDeleted(array $payload)
    {

    }
}
