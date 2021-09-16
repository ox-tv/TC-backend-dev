<?php


namespace App\Repository\Eloquent;


use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use App\Http\Resources\Pricing\PricingItem;
use App\Models\Pricing;
use App\Models\Transaction;
use App\Models\User;
use App\Repository\PricingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PricingRepository implements PricingRepositoryInterface
{
    public function addPricingToUser(User $user, Pricing $pricing)
    {
        $plan = $pricing->plan()->first();
        $paymentMethod = $pricing->paymentMethod()->first();

        if ($user->hero_due_at && $user->hero_due_at > Carbon::now()){
            $user->hero_due_at = $user->hero_due_at->addDays($plan->interval);
        }else{
            $user->hero_due_at = Carbon::now()->addDays($plan->interval);
        }

        // Add transaction
        $transaction = new Transaction();
        $transaction->type = Transaction::TYPE_DEPOSIT;
        $transaction->status = Transaction::STATUS_PENDING;
        $transaction->payment_method_id = $paymentMethod->id;
        $transaction->amount = $pricing->amount;
        $transaction->reference = Str::random(20);

        DB::transaction(function () use ($user, $pricing, $plan, $paymentMethod, $transaction){

            $transaction->save();

            $user->pricing()->attach($pricing->id, [
                'transaction_id' => $transaction->id,
                'metadata' => json_encode([
                    'pricing' => PricingItem::make($pricing),
                    'plan' => PlanItem::make($plan),
                    'payment_method' => PaymentMethodItem::make($paymentMethod),
                ])
            ]);

            $user->save();
        });

        return true;
    }
}