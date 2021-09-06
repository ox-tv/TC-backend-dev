<?php

namespace App\Http\Controllers;

use App\Http\Resources\Earning\EarningItem;
use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Http\Resources\Plan\PlanItem;
use App\Http\Resources\Pricing\PricingItem;
use App\Models\Channel;
use App\Models\Earning;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Pricing;
use App\Models\PricingUser;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VideoStatisticsDaily;
use App\Repository\PricingRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EarningController extends Controller
{
    public function index(Request $request)
    {
        $earningQuery = Earning::query();

        $filters = $request->get('filters', []);
        $userIdFilter = Arr::get($filters, 'user_id');
        $channelIdFilter = Arr::get($filters, 'channel_id');
        $statusFilter = Arr::get($filters, 'status');
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');
        $currencyFilter = Arr::get($filters, 'currency');

        if ($channelIdFilter){
            $channel = Channel::findOrFail($channelIdFilter);
            $userIdFilter = $channel->owner()->withTrashed()->firstOrFail()->id;
        }

        if ($request->is('/api/publisher/*')){
            $userIdFilter = auth()->id();
        }

        if ($userIdFilter){
            $earningQuery->where('user_id', $userIdFilter);
        }

        if ($statusFilter){
            $earningQuery->where('status', array_flip(Earning::STATUS_TEXT)[$statusFilter]);
        }

        if ($currencyFilter){
            $earningQuery->where('currency', $currencyFilter);
        }

        if ($fromFilter){
            $earningQuery->where('created_at', '>=', $fromFilter);
        }

        if ($toFilter){
            $earningQuery->where('created_at', '<=', $toFilter);
        }

        $earnings = $earningQuery->paginate();

        return EarningItem::collection($earnings);
    }

    public function total(Request $request)
    {
        $filters = $request->get('filters', []);
        $userIdFilter = Arr::get($filters, 'user_id');
        $channelIdFilter = Arr::get($filters, 'channel_id');
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        if ($channelIdFilter){
            $channel = Channel::findOrFail($channelIdFilter);
            $userIdFilter = $channel->owner()->withTrashed()->firstOrFail()->id;
        }

        if ($request->is('/api/publisher/*')){
            $userIdFilter = auth()->id();
        }

        $earningsQuery = Earning::when($fromFilter, function ($query, $fromFilter) {

            return $query->where('created_at', '>=', $fromFilter);

        })->when($toFilter, function ($query, $toFilter) {

            return $query->where('created_at', '<=', $toFilter);
        })->when($userIdFilter, function ($query, $userIdFilter) {

            return $query->where('user_id', $userIdFilter);
        });

        return response()->json([
            'amount' => $earningsQuery->sum('amount'),
        ]);
    }

    public function monthly(Request $request)
    {
        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(12)->firstOfMonth());
        $to = Arr::get($filters, 'to', (Carbon::now())->firstOfMonth());
        $userIdFilter = Arr::get($filters, 'user_id');
        $channelIdFilter = Arr::get($filters, 'channel_id');

        if ($channelIdFilter){
            $channel = Channel::findOrFail($channelIdFilter);
            $userIdFilter = $channel->owner()->withTrashed()->firstOrFail()->id;
        }

        if ($request->is('/api/publisher/*')){
            $userIdFilter = auth()->id();
        }

        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $earningsQuery = Earning::whereDate('created_at', '>=', $from_day)
                ->whereDate('created_at', '<=', $to_day)
                ->when($userIdFilter, function ($query, $userIdFilter) {
                    return $query->where('user_id', $userIdFilter);
                })->get();

            $statistics[$month->format("Y-m")] = [
                'amount' => $earningsQuery->sum('amount'),
            ];
        }

        return response()->json($statistics);
    }

    public function calcEarnings(Request $request)
    {
        $publishers = User::whereHas('channel')->get();

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(1)->startOfMonth());
        $to = Arr::get($filters, 'to', (Carbon::now())->subMonths(1)->endOfMonth());

        if($to >= (Carbon::now())->startOfMonth()->format('Y-m-d H:i:s')){
            abort(400, 'filter to must be less than first of this month');
        }

        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        $rate = config('general.points.to_usd_rate');

        abort_unless(is_numeric($rate), 'point to usd rate not found');

        foreach ($monthPeriods as $month) {
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            foreach ($publishers as $publisher){

                $points = VideoStatisticsDaily::where('channel_id', $publisher->channel->id)
                    ->where('date', '>=', $from_day)
                    ->where('date', '<=', $to_day)
                    ->sum('points');

                $earningAmount = $points * $rate;
                $earningAmount = ($earningAmount > 0)? $earningAmount: 0;

                $earning = Earning::where('user_id', $publisher->id)
                    ->whereDate('date', $month->startOfMonth()->format("Y-m-d"))
                    ->firstOr(function () use ($publisher, $month) {
                        $e = new Earning();
                        $e->user_id = $publisher->id;
                        $e->date = $month->startOfMonth()->format("Y-m-d");
                        return $e;
                    });

                $earning->status = ($earningAmount > 0)? Earning::STATUS_PENDING: Earning::STATUS_NA;
                $earning->amount = $earningAmount;


                DB::transaction(function () use ($earning){
                    // Add transaction
                    if ($earning->amount > 0){
                        $transaction = $earning->transaction;
                        if(!$transaction){
                            $transaction = new Transaction();
                        }
                        $transaction->amount = $earning->amount;
                        $transaction->type = Transaction::TYPE_WITHDRAW;
                        $transaction->status = Transaction::STATUS_PENDING;
                        $transaction->save();
                        $earning->transaction_id = $transaction->id;
                    }

                    $earning->save();
                });
            }
        }

        return response()->json(["status" => "ok"]);
    }

    public function setToPaid(Request $request, $earningId)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference' => 'required|string',
        ]);

        $earning = Earning::whereId($earningId)
            ->where('status', Earning::STATUS_PENDING)
            ->firstOrFail();

        $earning->status = Earning::STATUS_PAID;

        $transaction = $earning->transaction;
        $transaction->payment_method_id = $request->get('payment_method_id');
        $transaction->reference = $request->get('reference');
        $transaction->status = Transaction::STATUS_COMPLETED;
        $transaction->completed_at = date('Y-m-d H:i:s');

        DB::transaction(function () use ($earning, $transaction, $request){
            $transaction->save();
            $earning->save();
        });

        return response()->json(["status" => "ok"]);
    }
}
