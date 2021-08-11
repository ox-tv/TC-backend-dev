<?php


namespace App\Repository\Eloquent;


use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use App\Http\Resources\Pricing\PricingItem;
use App\Models\Pricing;
use App\Models\User;
use App\Repository\PricingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        DB::transaction(function () use ($user, $pricing, $plan, $paymentMethod){

            $user->pricing()->attach($pricing->id, [
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