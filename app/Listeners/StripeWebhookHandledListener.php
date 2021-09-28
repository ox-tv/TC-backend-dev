<?php

namespace App\Listeners;

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
        $payload = $event->payload;
        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type']));

        if (method_exists($this, $method)) {
            $this->{$method}($payload);
            return true;
        }

        return false;
    }

    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        if (empty($payload['data']['object']['customer'])){
            return false;
        }

        $user = Cashier::findBillable($payload['data']['object']['customer']);

        if (!$user){
            return false;
        }

        $data = $payload['data']['object'];

        if (empty($data['metadata']['pricing_user_id'])){
            return false;
        }

        $pricingUser = PricingUser::find($data['metadata']['pricing_user_id']);

        if (!$pricingUser){
            return false;
        }

        if ($pricingUser->status != PricingUser::STATUS_PENDING){
            return false;
        }

        if ($user->subscribedToPrice($pricingUser->pricing->external_id, 'default')) {
            DB::transaction(function () use ($pricingUser, $user){
                $transaction = $pricingUser->transaction;
                $plan = $pricingUser->pricing->plan;
                $transaction->status = Transaction::STATUS_COMPLETED;
                $pricingUser->status = PricingUser::STATUS_COMPLETED;
                $pricingUser->save();
                $transaction->save();

                if ($user->hero_due_at && $user->hero_due_at > Carbon::now()){
                    $user->hero_due_at = $user->hero_due_at->addDays($plan->interval);
                }else{
                    $user->hero_due_at = Carbon::now()->addDays($plan->interval);
                }
                $user->save();
            });
        }

        return true;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload)
    {

    }

    protected function handleCustomerSubscriptionDeleted(array $payload)
    {

    }
}
