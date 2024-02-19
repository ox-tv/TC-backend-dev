<?php

namespace App\Http\Controllers;

use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Earning\EarningItem;
use App\Http\Resources\PaymentDetails\PaymentDetailsResource;
use App\Models\Channel;
use App\Models\Earning;
use App\Models\Monetization;
use App\Models\MonetizePoint;
use App\Models\Option;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MonetizationController extends Controller
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

        $earningQuery->orderByDesc('date');

        $earnings = $earningQuery->paginate();

        return EarningItem::collection($earnings);
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

    public function qualifiedChannels()
    {
        $channels = Channel::whereNotNull('monetization_qualified_at')
            ->where('monetization_qualified_at', '<=', Carbon::now())
            ->orderBy('monetization_qualified_at', 'DESC')
            ->paginate();

        $channels->load('owner.verifiedPaymentDetails');

        return ChannelResource::collection($channels);
    }


}
