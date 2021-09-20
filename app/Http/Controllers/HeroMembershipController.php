<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use App\Http\Resources\Pricing\PricingItem;
use App\Libraries\CoinBaseClient;
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

class HeroMembershipController extends Controller
{
    private $pricingRepository;

    public function __construct(PricingRepositoryInterface $pricingRepository)
    {
        $this->pricingRepository = $pricingRepository;
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
        if (!$pricing->paymentMethod){
            return response()->json([
                'status' => 'error',
                'message' => 'Payment method not found',
            ], 404);
        }

        if (strtolower($pricing->paymentMethod->name) == 'coinbase'){
            return $this->processPaymentCoinBase($request, $pricing);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Payment method not supported',
        ], 404);
    }

    public function processPaymentCoinBase(Request $request, Pricing $pricing)
    {
        $exists = $pricing->plan()->where('status', Plan::STATUS_ACTIVE)->exists();
        abort_unless($exists, 404);

        $user = auth()->user();
        $plan = $pricing->plan()->first();
        $paymentMethod = $pricing->paymentMethod()->first();

        $name = $plan->name;
        $description = "Payment by " . $user->email;

        $name = mb_substr( $name, 0, 100 );
        $description = mb_substr( $description, 0, 200 );

        $redirectUrl = config('payment.coinbase.redirect_url');
        $cancelUrl = config('payment.coinbase.cancel_url');

        DB::beginTransaction();

        try {
            $transaction = new Transaction();
            $transaction->type = Transaction::TYPE_DEPOSIT;
            $transaction->status = Transaction::STATUS_PENDING;
            $transaction->payment_method_id = $paymentMethod->id;
            $transaction->amount = $pricing->amount;
            $transaction->save();

            $pricingUser = new PricingUser();
            $pricingUser->user_id = $user->id;
            $pricingUser->pricing_id = $pricing->id;
            $pricingUser->status = PricingUser::STATUS_PENDING;
            $pricingUser->metadata = json_encode([
                'coinbase_status' => 'NEW',
                'pricing' => PricingItem::make($pricing),
                'plan' => PlanItem::make($plan),
                'payment_method' => PaymentMethodItem::make($paymentMethod),
            ]);
            $pricingUser->transaction_id = $transaction->id;
            $pricingUser->save();

            $metadata = [
                'pricing_user_id'  => $pricingUser->id,
                'source' => 'hero_membership'
            ];

            $client = new CoinBaseClient();

            $result = $client->createCharge(
                $name, $description, $pricing->amount, $pricing->currency, $metadata, $redirectUrl, $cancelUrl);

            if (!$result['success']){
                throw new Exception($result['message']);
            }

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'redirect_to' => $result['data']['hosted_url']
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
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
