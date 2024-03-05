<?php

namespace App\Http\Controllers;

use App\Exports\MonetizationExport;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Monetization\MonetizationPayoutResource;
use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use App\Models\Monetization;
use App\Models\MonetizationPayout;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MonetizationController extends Controller
{
    public function adminPayouts(Request $request)
    {
        $query = MonetizationPayout::query();

        $filters = $request->get('filters', []);
        $monthFilter = Arr::get($filters, 'month');
        $statusFilter = Arr::get($filters, 'status');

        if ($monthFilter){
            $month = Carbon::parse($monthFilter)->startOfMonth();
        }else{
            $month = Carbon::now()->startOfMonth();
        }

        $monetization = Monetization::whereDate('month', $month)->first();
        $query->where('monetization_id', $monetization->id??0);

        if ($statusFilter){
            $query->where('status', array_flip(MonetizationPayout::STATUS_TEXT)[$statusFilter]);
        }

        $payouts = $query->paginate();

        $payouts->load('channel');

        return MonetizationPayoutResource::collection($payouts);
    }

    public function publisherPayouts(Request $request)
    {
        $query = MonetizationPayout::query();

        $filters = $request->get('filters', []);
        $statusFilter = Arr::get($filters, 'status');


        if ($statusFilter){
            $query->where('status', array_flip(MonetizationPayout::STATUS_TEXT)[$statusFilter]);
        }

        $query->orderBy('created_at', 'desc');

        $payouts = $query->paginate();

        $payouts->load(['channel', 'monetization']);

        return MonetizationPayoutResource::collection($payouts);
    }



    public function setBudget(Request $request)
    {
        $request->validate([
            'budget' => 'required|numeric|gt:0',
            'month' => 'required|date|after:'.Carbon::now()->endOfMonth(),
        ],[
            'month.after' => "month value can not be current or previous month."
        ]);

        $budget = $request->get('budget');
        $month = Carbon::parse($request->get('month'));

        $monetization = Monetization::whereDate('month', $month->format('Y-m-d'))->first();

        if (!$monetization){
            $monetization = new Monetization();
            $monetization->month = $month->format('Y-m');
            $monetization->status = Monetization::STATUS_UNPAID;
        }

        $monetization->budget = $budget;
        $monetization->save();

        return response()->json(["message" => "ok"]);
    }

    public function getBudget(Request $request)
    {
        $request->validate([
            'year' => 'required|gte:2020',
        ]);

        $date = Carbon::createFromDate($request->get('year'),1, 1);
        $from = $date->copy()->startOfYear()->format('Y-m-d');
        $to = $date->copy()->endOfYear()->format('Y-m-d');

        $records = Monetization::orderBy('month', 'ASC')
            ->whereDate('month', '>=', $from)
            ->whereDate('month', '<=', $to)
            ->get();

        return $records;
    }

    public function markAsPaid(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', Rule::exists('monetization_payouts', 'id')],
        ]);

        MonetizationPayout::whereIn('id', $request->get('ids'))
            ->update(['status' => MonetizationPayout::STATUS_PAID]);

        return response()->json(["message" => "ok"]);
    }

    public function qualifiedChannels()
    {
        $channels = Channel::whereNotNull('monetization_qualified_at')
            ->where('monetization_qualified_at', '<=', Carbon::now())
            ->orderBy('monetization_qualified_at', 'DESC')
            ->paginate();

        $channels->load('owner.verifiedPaymentDetails');

        return ChannelResource::collection($channels);
    }

    public function exportMonetizationPayouts(Request $request)
    {
        $request->validate([
            'month' => 'required|date',
        ]);

        $month = Carbon::parse($request->get('month'))->startOfMonth();

        $monetization = Monetization::whereDate('month', $month)->first();

        $payouts = MonetizationPayout::whereNotNull('wallet_address')
            ->where('monetization_id', $monetization->id??0)->get();

        $payouts->load(['channel']);

        $fileName = 'monetization-' . $month->format('Y-m') . '.csv';

        return Excel::download(new MonetizationExport($payouts), $fileName, \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }

    public function qualifiedStatus()
    {
        $result = [];

        $channel = auth('api')->user()->channel()->firstOrFail();

        $result['is_qualified'] = (bool) $channel->monetization_qualified_at;
        $result['qualified_at'] = $channel->monetization_qualified_at;

//        $result['watch_time_total'] = intval(channel2StatisticsDaily::where('channel_id', $channel->id)->sum('watch_time_total'));
//        $result['subscribers_total'] = intval(channel2StatisticsDaily::where('channel_id', $channel->id)->sum('subscribers_total')) - intval(channel2StatisticsDaily::where('channel_id', $channel->id)->sum('unsubscribers_total'));

        $result['subscribers_total'] = intval($channel->watch_time);
        $result['watch_time_total'] = intval($channel->subscribers_count);

        return response()->json($result);
    }

    public function getTotalDistributedMoney()
    {
        $month = Carbon::now()->startOfMonth();

        $monetization = Monetization::whereDate('month', $month)->firstOrFail();

        return $monetization->budget;
    }
}
