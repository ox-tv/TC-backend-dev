<?php

namespace App\Http\Controllers;

use App\Exports\CampaignStatisticsExport;
use App\Http\Resources\CryptoCampaign\CryptoCampaignResource;
use App\Models\CryptoCampaign;
use App\Models\CryptoCampaignStatisticsDaily;
use App\Models\CryptoCurrency;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CryptoCampaignController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->get('filters', []);
        $searchFilter = Arr::get($filters, 'search');
        $statusFilter = Arr::get($filters, 'status');

        $perPage = $request->get('per_page') ?: 15;

        $query = CryptoCampaign::query();

        if ($searchFilter){
            $query->searchName($searchFilter);
        }

        if ($statusFilter){
            $query->status($statusFilter);
        }

        // Sorting
        switch ($request->get('sort')){
            case 'oldest': {
                $query->orderBy('created_at', 'asc');
                break;
            }
            default: {
                $query->orderBy('created_at', 'desc');
            }
        }

        $campaigns = $query->paginate($perPage);

        return CryptoCampaignResource::collection($campaigns);
    }

    public function show($id)
    {
        $campaign = CryptoCampaign::where('id', $id)->firstOrFail();
        $campaign->load(['crypto_currencies']);

        return CryptoCampaignResource::make($campaign);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'headline' => 'nullable',
            'description' => 'nullable',
            'exchange_name' => 'required',
            'exchange_main_url' => 'required',
            'exchange_referral_url' => ['required', 'url'],
            'thumbnail' => 'nullable',
            'status' => ['required', Rule::in(CryptoCampaign::STATUS_TEXT)],
            'crypto_currencies' => ['required', 'array'],
            'crypto_currencies.*' => ['required', Rule::exists('crypto_currencies', 'id')],
        ]);

        $campaign = new CryptoCampaign();
        $campaign->name = $request->get('name');
        $campaign->headline = $request->get('headline');
        $campaign->description = $request->get('description');
        $campaign->exchange_name = $request->get('exchange_name');
        $campaign->exchange_main_url = $request->get('exchange_main_url');
        $campaign->exchange_referral_url = $request->get('exchange_referral_url');
        $campaign->thumbnail = $request->get('thumbnail');
        $campaign->status = $request->get('status');

        DB::transaction(function () use ($request, $campaign){
            $campaign->save();
            $campaign->crypto_currencies()->sync($request->get('crypto_currencies'));
        });

        return CryptoCampaignResource::make($campaign);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'headline' => 'nullable',
            'description' => 'nullable',
            'exchange_name' => 'required',
            'exchange_main_url' => 'required',
            'exchange_referral_url' => ['required', 'url'],
            'thumbnail' => 'nullable',
            'status' => ['required', Rule::in(CryptoCampaign::STATUS_TEXT)],
        ]);

        $campaign = CryptoCampaign::where('id', $id)->firstOrFail();
        $campaign->name = $request->get('name');
        $campaign->headline = $request->get('headline');
        $campaign->description = $request->get('description');
        $campaign->exchange_name = $request->get('exchange_name');
        $campaign->exchange_main_url = $request->get('exchange_main_url');
        $campaign->exchange_referral_url = $request->get('exchange_referral_url');
        $campaign->thumbnail = $request->get('thumbnail');
        $campaign->status = $request->get('status');

        DB::transaction(function () use ($request, $campaign){
            $campaign->save();
            $campaign->crypto_currencies()->sync($request->get('crypto_currencies'));
        });

        return CryptoCampaignResource::make($campaign);
    }

    public function storeStatistic(Request $request, $cryptoCurrencyId, $campaignId)
    {
        $request->merge([
            'crypto_currency_id' => $cryptoCurrencyId,
            'campaign_id' => $campaignId,
        ]);

        $request->validate([
            'crypto_currency_id' => ['required', Rule::exists('crypto_currencies', 'id')],
            'campaign_id' => ['required', Rule::exists('crypto_campaigns', 'id')],
        ]);

        $statistics = CryptoCampaignStatisticsDaily::firstOrNew([
            'crypto_currency_id' => (int) $cryptoCurrencyId,
            'campaign_id' => (int) $campaignId,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->total_clicks += 1;

        if (auth('api')->check()){
            $statistics->registered_users_clicks += 1;
        }else{
            $statistics->unknown_users_clicks += 1;
        }

        $statistics->save();

        return response()->json(['message' => 'ok']);
    }

    public function destroy($campaignId)
    {
        $campaign = CryptoCampaign::where('id', $campaignId)->firstOrFail();

        $campaign->crypto_currencies()->detach();
        $campaign->delete();

        return response()->json(['message' => 'ok']);
    }

    public function statistics(Request $request, $campaignId)
    {
        $campaign = CryptoCampaign::where('id', $campaignId)->firstOrFail();

        $result = [
            'overview' => [
                'total_days_active' => 0,
                'total_clicks' => 0,
                'avarage_clicks_per_day' => 0,
                'max_clicks_per_day' => 0,
                'min_clicks_per_day' => 0,
                'registered_users_total_clicks' => 0,
                'unknown_users_total_clicks' => 0,
            ],
            'crypto_currencies' => [],
            'statistics' => []
        ];

        $result['overview']['total_days_active'] = Carbon::now()->diffInDays($campaign->created_at);
        $result['overview']['total_clicks'] = CryptoCampaignStatisticsDaily::where('campaign_id', $campaign->id)->sum('total_clicks');
        $result['overview']['avarage_clicks_per_day'] = $result['overview']['total_days_active'] > 0 ? $result['overview']['total_clicks'] / $result['overview']['total_days_active'] : 0;
        $result['overview']['max_clicks_per_day'] = CryptoCampaignStatisticsDaily::where('campaign_id', $campaign->id)->max('total_clicks');
        $result['overview']['min_clicks_per_day'] = CryptoCampaignStatisticsDaily::where('campaign_id', $campaign->id)->min('total_clicks');
        $result['overview']['registered_users_total_clicks'] = CryptoCampaignStatisticsDaily::where('campaign_id', $campaign->id)->sum('registered_users_clicks');
        $result['overview']['unknown_users_total_clicks'] = CryptoCampaignStatisticsDaily::where('campaign_id', $campaign->id)->sum('unknown_users_clicks');

        // Statistics by coins
        $cryptoCurrencies = CryptoCurrency::whereHas('cryptoCampaigns')->get();
        foreach ($cryptoCurrencies as $cryptoCurrency){
            $result['crypto_currencies'][$cryptoCurrency->id] = [
                'symbol' => $cryptoCurrency->symbol,
                'name' => $cryptoCurrency->name,
                'total_clicks' => CryptoCampaignStatisticsDaily::where('campaign_id', $campaign->id)->where('crypto_currency_id', $cryptoCurrency->id)->sum('total_clicks')
            ];
        }

        // Statistics by campaign id
        $filters = $request->get('filters', []);
        $period = Arr::get($filters, 'statistics_period', 'last_30d');

        switch ($period) {
            case 'last_7d';
                $from = Carbon::now()->subDays(7)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_14d';
                $from = Carbon::now()->subDays(14)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_90d';
                $from = Carbon::now()->subDays(90)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_180d';
                $from = Carbon::now()->subMonths(5)->startOfDay();
                $to = Carbon::now()->endOfMonth();
                break;
            case 'last_365d';
                $from = Carbon::now()->subMonths(11)->startOfDay();
                $to = Carbon::now()->endOfMonth();
                break;
            case 'last_30d';
            default;
                $from = Carbon::now()->subDays(30)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
        }

        $result['statistics'] = in_array($period, ['last_365d', 'last_180d'])? $this->monthlyStatistics($campaign, $from, $to) : $this->dailyStatistics($campaign, $from, $to);

        if ($request->is('*/export')){

            $exportData = [];
            $exportData[] = ['Statistics: ', $campaign->name];
            $exportData[] = ['Duration: ', sprintf('%s to %s', $campaign->created_at->format('Y-m-d'), Carbon::now()->format('Y-m-d'))];
            $exportData[] = ['Total days active: ', $result['overview']['total_days_active']];
            $exportData[] = ['Total click thru:', $result['overview']['total_clicks']];
            $exportData[] = ['Day with most click thru:', $result['overview']['max_clicks_per_day']];
            $exportData[] = ['Day with least click thru:', $result['overview']['min_clicks_per_day']];
            $exportData[] = ['Registered users:', $result['overview']['registered_users_total_clicks']];
            $exportData[] = ['Unknown users:', $result['overview']['unknown_users_total_clicks']];
            $exportData[] = [''];
            $exportData[] = ['Click thru, distrubution per coin:'];

            foreach ($result['crypto_currencies'] as $row){
                $exportData[] = [$row['name'], $row['total_clicks']];
            }

            $exportData[] = [''];
            $exportData[] = ['Click thru/ day:'];

            foreach ($result['statistics'] as $row){
                $exportData[] = [$row['date'], $row['total_clicks']];
            }

            return Excel::download(new CampaignStatisticsExport(collect($exportData)), "campaign-{$campaign->id}-statistics.xlsx");
        }


        return response()->json($result);
    }

    private function dailyStatistics($campaign, $from, $to): array
    {
        $statistics = [];
        $periods = CarbonPeriod::create($from, '1 day', $to);

        foreach ($periods as $day) {
            $query = CryptoCampaignStatisticsDaily::where('campaign_id', $campaign->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')));

            $statistics[$day->format('Y-m-d')] = [
                'date' => $day->format('Y-m-d'),
                'registered_users_clicks' => natural_intval($query->sum('registered_users_clicks')),
                'unknown_users_clicks' => natural_intval($query->sum('unknown_users_clicks')),
                'total_clicks' => natural_intval($query->sum('total_clicks')),
            ];
        }

        return $statistics;
    }

    private function monthlyStatistics($campaign, $from, $to): array
    {
        $statistics = [];
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $date = $month->copy()->startOfMonth()->format("Y-m-d");

            $query = CryptoCampaignStatisticsDaily::where('campaign_id', $campaign->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth());

            $statistics[$date] = [
                'date' => $date,
                'registered_users_clicks' => natural_intval($query->sum('registered_users_clicks')),
                'unknown_users_clicks' => natural_intval($query->sum('unknown_users_clicks')),
                'total_clicks' => natural_intval($query->sum('total_clicks')),
            ];
        }

        return $statistics;
    }
}
