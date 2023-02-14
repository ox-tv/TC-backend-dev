<?php

namespace App\Http\Controllers;

use App\Http\Resources\CryptoCampaign\CryptoCampaignResource;
use App\Models\CryptoCampaign;
use App\Models\CryptoCampaignStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $statistics->total_click += 1;

        if (auth('api')->check()){
            $statistics->registered_users_click += 1;
        }else{
            $statistics->unknown_users_click += 1;
        }

        $statistics->save();

        return response()->json(['message' => 'ok']);
    }
}
