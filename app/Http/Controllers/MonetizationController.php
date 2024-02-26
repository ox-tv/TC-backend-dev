<?php

namespace App\Http\Controllers;

use App\Exports\MonetizationExport;
use App\Exports\PublisherEarningsExport;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Earning\EarningItem;
use App\Http\Resources\Monetization\MonetizationPayoutResource;
use App\Http\Resources\PaymentDetails\PaymentDetailsResource;
use App\Models\Channel;
use App\Models\Earning;
use App\Models\Monetization;
use App\Models\MonetizationPayout;
use App\Models\MonetizePoint;
use App\Models\Option;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MonetizationController extends Controller
{
    public function payouts(Request $request)
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
}
