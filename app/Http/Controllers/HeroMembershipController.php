<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use App\Http\Resources\Pricing\PricingItem;
use App\Http\Resources\PricingUser\PricingUserItem;
use App\Models\Earning;
use App\Models\Plan;
use App\Models\Pricing;
use App\Models\PricingUser;
use App\Models\Transaction;
use App\Models\User;
use App\Repository\PricingRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Cashier\Exceptions\IncompletePayment;

class HeroMembershipController extends Controller
{
    private $pricingRepository;

    public function __construct(PricingRepositoryInterface $pricingRepository)
    {
        $this->pricingRepository = $pricingRepository;
    }

    public function index(Request $request)
    {
        $filters = $request->get('filters', []);
        $userIdFilter = Arr::get($filters, 'user_id');
        $statusFilter = Arr::get($filters, 'status');

        if(!$request->is('api/admin/*')){
            $userIdFilter = auth('api')->id();
        }

        $pricingUserQuery = PricingUser::orderBy('created_at','desc');

        if ($userIdFilter){
            $pricingUserQuery->where('user_id', $userIdFilter);
        }

        $statuses = array_flip(PricingUser::STATUS_TEXT);
        if ($statusFilter && array_key_exists($statusFilter, $statuses)){
            $pricingUserQuery->where('status', $statuses[$statusFilter]);
        }

        return PricingUserItem::collection($pricingUserQuery->paginate());
    }

    public function store(Request $request, Pricing $pricing)
    {
        $exists = $pricing->plan()->where('status', Plan::STATUS_ACTIVE)->exists();

        abort_unless($exists, 404);

        if($request->is('api/admin/*')){
            $user = User::findOrFail($request->get('user_id'));
        }else{
            $user = auth('api')->user();
        }

        $this->pricingRepository->addPricingToUser($user, $pricing);

        return response()->json(['message' => 'ok']);
    }

    public function processPayment(Request $request, Pricing $pricing)
    {
        $exists = $pricing->plan()->where('status', Plan::STATUS_ACTIVE)->exists();
        abort_unless($exists, 404);

        $plan = $pricing->plan()->first();
        if (!$plan){
            return response()->json([
                'status' => 'error',
                'message' => 'Plan not found',
            ], 404);
        }

        $paymentMethod = $pricing->paymentMethod()->first();
        if (!$paymentMethod){
            return response()->json([
                'status' => 'error',
                'message' => 'Payment method not found',
            ], 404);
        }

        if (strtolower($paymentMethod->name) == 'stripe'){
            return $this->processPaymentStripe($request, $pricing, $plan, $paymentMethod);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Payment method not supported',
        ], 404);
    }

    public function processPaymentStripe(Request $request, Pricing $pricing, $plan, $paymentMethod)
    {
        $user = auth('api')->user();

        if ($user->subscribed('default')) {
            // one time buy
            $checkout = $request->user()->checkout($pricing->external_id, [
                'success_url' => config('services.stripe.checkout_success_url'),
                'cancel_url' => config('services.stripe.checkout_failure_url'),
            ]);
        }else{
            // subscription
            $checkout = $request->user()
                ->newSubscription('default', $pricing->external_id)
                ->checkout([
                    'success_url' => config('services.stripe.checkout_success_url'),
                    'cancel_url' => config('services.stripe.checkout_failure_url'),
                ]);
        }

        $result['redirect_to'] = $checkout->url;
        $result['status'] = 'ok';

        return response()->json($result);
    }

    public function earningsTotal(Request $request, $userId = null)
    {
        $filters = $request->get('filters', []);
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        $pricingUsers = PricingUser::when($userId, function ($query, $userId) {

            return $query->where('user_id', $userId);

        })->when($fromFilter, function ($query, $fromFilter) {

            return $query->where('created_at', '>=', $fromFilter);

        })->when($toFilter, function ($query, $toFilter) {

            return $query->where('created_at', '<=', $toFilter);
        });

        return [
            'amount' => $pricingUsers->sum('metadata->pricing->amount'),
            'currency' => 'USD',
        ];
    }

    public function earningsMonthly(Request $request, $userId = null)
    {
        $result = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(12)->firstOfMonth());
        $to = Arr::get($filters, 'to', (Carbon::now())->firstOfMonth());
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);


        foreach ($monthPeriods as $month) {
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $pricingUsers = PricingUser::when($userId, function ($query, $userId) {
                    return $query->where('user_id', $userId);
                })
                ->whereDate('created_at', '>=', $from_day)
                ->whereDate('created_at', '<=', $to_day);

            $result[$month->format("Y-m")] = [
                'amount' => $pricingUsers->sum('metadata->pricing->amount'),
                'currency' => 'USD',
            ];
        }

        return $result;
    }

    public function earningsDaily(Request $request, $userId = null)
    {
        $statistics = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subDays(30)->format('Y-m-d'));
        $to = Arr::get($filters, 'to', (Carbon::now())->format('Y-m-d H:i:s'));


        $periods = CarbonPeriod::create($from, '1 day', $to);
        abort_unless(count($periods) <= 31, 400, 'timespan between from and to is more than 1 month');

        foreach ($periods as $day) {

            $pricingUsers = PricingUser::when($userId, function ($query, $userId) {
                    return $query->where('user_id', $userId);
                })
                ->whereDate('created_at', '>=', $day->format('Y-m-d 00:00:00'))
                ->whereDate('created_at', '<=', $day->format('Y-m-d 23:59:59'));

            $statistics[$day->format('Y-m-d')] = [
                'amount' => $pricingUsers->sum('metadata->pricing->amount'),
                'currency' => 'USD',
            ];
        }

        return $statistics;
    }

}
