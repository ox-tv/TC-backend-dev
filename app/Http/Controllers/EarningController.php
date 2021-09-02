<?php

namespace App\Http\Controllers;

use App\Http\Resources\Earning\EarningItem;
use App\Models\Channel;
use App\Models\Earning;
use App\Models\Plan;
use App\Models\Pricing;
use App\Models\PricingUser;
use App\Models\User;
use App\Repository\PricingRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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
            $userIdFilter = $channel->owner->id;
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
            $userIdFilter = $channel->owner->id;
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
            $userIdFilter = $channel->owner->id;
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
}
