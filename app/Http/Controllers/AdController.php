<?php

namespace App\Http\Controllers;

use App\Http\Resources\Ad\AdCampaignResource;
use App\Http\Resources\Ad\AdDiscountResource;
use App\Http\Resources\Ad\AdPricingResource;
use App\Http\Resources\Ad\AdSlotResource;
use App\Models\AdCampaign;
use App\Models\AdDiscount;
use App\Models\AdSlot;
use App\Models\Option;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AdController extends Controller
{
    public function indexCampaign(Request $request)
    {
        $perPage = $request->get('per_page') ?: 15;
        $isAdminRoute = $request->is('api/admin/*');

        $filters = $request->get('filters', []);
        $companyIdFilter = Arr::get($filters, 'company_id');
        $searchFilter = Arr::get($filters, 'search');
        $statusFilter = Arr::get($filters, 'status');


        $query = AdCampaign::query();

        if ($companyIdFilter){
            $query->whereHas('company', function ($q) use ($companyIdFilter){

                if (is_numeric($companyIdFilter)){
                    $q->where('id', $companyIdFilter);
                }else{
                    $q->where('name', 'LIKE', '%'.$companyIdFilter.'%');
                }
            });
        }

        if ($searchFilter){
            if (is_numeric($searchFilter)){
                $query->where('id', $searchFilter);
            }else{
                $query->where('name', 'LIKE', '%'.$searchFilter.'%');
            }
        }

        if ($statusFilter){
            $query->status($statusFilter);
        }

        switch ($request->get('sort')){
            case 'newest': {
                $query->orderBy('created_at', 'DESC');
            }
            case 'oldest':
            default: {
                $query->orderBy('created_at', 'ASC');
            }
        }

        $campaigns = $query->paginate($perPage);

        $campaigns->load(['slots', 'company']);

        return AdCampaignResource::collection($campaigns);
    }
    
    public function storeCampaign(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'company_id' => ['required', Rule::exists('companies', 'id')],
            'status' => ['required', Rule::in(AdCampaign::STATUS_TEXT)],
            'slots' => ['nullable', 'array'],
            'slots.*.date' => ['required', 'date_format:Y-m-d'],
            'slots.*.tier' => ['required', 'in:1,2,3,4,5'],
            'slots.*.quantity' => ['required', 'numeric'],
            'tiers_data' => ['nullable'],
        ]);

        $campaign = new AdCampaign();
        $campaign->name = $request->get('name');
        $campaign->company_id = $request->get('company_id');
        $campaign->status = $request->get('status');
        $campaign->data = $request->get('tiers_data');
        $campaign->save();

        if ($request->get('slots')){
            foreach ($request->get('slots') as $row){
                $slot = new AdSlot();
                $slot->ad_campaign_id = $campaign->id;
                $slot->date = $row['date'];
                $slot->tier = $row['tier'];
                $slot->quantity = $row['quantity'];
                $slot->price = 100;
                $slot->save();
            }
        }

        $campaign->load(['slots', 'company']);

        return AdCampaignResource::make($campaign);
    }

    public function updateCampaign(Request $request, $campaignId)
    {
        $campaign = AdCampaign::where('id', $campaignId)->firstOrFail();

        $validationRules = [
            'name' => ['nullable'],
            'status' => ['nullable', Rule::in(AdCampaign::STATUS_TEXT)],
            'tiers_data' => ['nullable'],
        ];

        if ($campaign->status != AdCampaign::STATUS_ARCHIVED){
            $validationRules['slots'] = ['nullable', 'array'];
            $validationRules['slots.*.date'] = ['required', 'date_format:Y-m-d'];
            $validationRules['slots.*.tier'] = ['required', 'in:1,2,3,4,5'];
            $validationRules['slots.*.quantity'] = ['required', 'numeric'];
            $validationRules['slots.*.id'] = ['nullable', Rule::exists('ad_slots', 'id')];
        }

        $request->validate($validationRules);

        // update campaign
        $campaign->name = $request->get('name', $campaign->name);
        $campaign->data = $request->get('tiers_data', $campaign->data);

        if ($campaign->status != AdCampaign::STATUS_ARCHIVED){
            $campaign->status = $request->get('status', $campaign->status);
        }

        $campaign->save();

        // update slots
        if ($campaign->status != AdCampaign::STATUS_ARCHIVED){

            $preExistingSlotIds = array_column((array) $request->get('slots'), 'id');
            AdSlot::where('ad_campaign_id', $campaign->id)->whereNotIn('id', $preExistingSlotIds)->delete();

            if ($request->get('slots')){
                foreach ($request->get('slots') as $row){

                    if (!empty($row['id'])){
                        continue;
                    }

                    $slot = new AdSlot();
                    $slot->ad_campaign_id = $campaign->id;
                    $slot->date = $row['date'];
                    $slot->tier = $row['tier'];
                    $slot->quantity = $row['quantity'];
                    $slot->price = 100;
                    $slot->save();
                }
            }
        }

        $campaign->load(['slots', 'company']);

        return AdCampaignResource::make($campaign);
    }

    public function showCampaign($campaignId)
    {
        $campaign = AdCampaign::where('id', $campaignId)->firstOrFail();

        $campaign->load(['slots', 'company']);

        return AdCampaignResource::make($campaign);
    }

    public function destroyCampaign($campaignId)
    {
        $campaign = AdCampaign::where('id', $campaignId)->firstOrFail();
        $campaign->slots()->delete();
        $campaign->delete();

        return response()->json(["message" => "ok"]);
    }

    public function filledSlotes(Request $request)
    {
        $request->validate([
            'filters.from' => ['nullable','date_format:Y-m-d'],
            'filters.to' => ['nullable','date_format:Y-m-d', 'after:from'],
        ]);

        $filters = $request->get('filters', []);
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        $from = $fromFilter? Carbon::parse($fromFilter) : Carbon::now();
        $to = $toFilter? Carbon::parse($toFilter) : $from->clone()->addDays(14);

        $slotes = AdSlot::where('date', '>=', $from)->where('date', '<=', $to)->get();

        return AdSlotResource::collection($slotes);
    }


    // Add Manager Settings
    public function storeSettings(Request $request)
    {
        $request->validate([
            'tiers_data' => 'required',
            'tiers_data.*.tier' => 'required|in:1,2,3,4,5',
            'tiers_data.*.cpm' => 'required|numeric',
            'tiers_names.*.article' => 'required',
            'tiers_names.*.group' => 'required',

            'tiers_discounts' => 'nullable',
            'tiers_discounts.*.id' => ['nullable', Rule::exists('ad_discounts', 'id')],
            'tiers_discounts.*.tier' => 'required|in:1,2,3,4,5',
            'tiers_discounts.*.type' => 'required|in:fixed,percent',
            'tiers_discounts.*.amount' => 'required|numeric',
            'tiers_discounts.*.start_at' => ['nullable','date_format:Y-m-d'],
            'tiers_discounts.*.end_at' => ['nullable','date_format:Y-m-d', 'after:discount.*.start_at'],
        ]);

        $optionData = $request->only('tiers_data');

        foreach ($optionData as $key => $row){
            $pps = ($row['cpm'] * (25000 * 0.001) ) / 7 / 5; // TODO: cpm * avarage(4 week reach) * 0.001 / 7 / 5
            $optionData[$key]['pps'] = [
                'regular' => 10,
                'final' => 9,
            ];
        }
        dd($optionData);

        Option::set(Option::AD_TIERS_INFO, json_encode());

        // Discounts
        $preExistingDiscountIds = array_column((array) $request->get('tiers_discounts'), 'id');
        AdDiscount::whereNotIn('id', $preExistingDiscountIds)
            ->where(function ($q){
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>', Carbon::now());
            })
            ->delete();

        if ($discounts = $request->get('tiers_discounts')){
            foreach ($discounts as $row){

                $discount = new AdDiscount();

                if (!empty($row['id'])){
                    $discount = AdDiscount::where('id', $row['id'])->first();
                }

                $discount->tier = $row['tier'];
                $discount->type = $row['type'];
                $discount->amount = $row['amount'];
                $discount->start_at = $row['start_at']?? null;
                $discount->end_at = $row['end_at']?? null;
                $discount->save();
            }
        }

        return response()->json(["message" => "ok"]);
    }

    public function getSettings(Request $request)
    {
        $result = [
            'tiers_cpm' => [],
            'tiers_names' => [],
            'tiers_discounts' => [],
        ];

        $tiersInfo = Option::get(Option::AD_TIERS_INFO)->value ?? null;
        $tiersInfo = $tiersInfo? json_decode($tiersInfo, true): null;

        $result['tiers_cpm'] = $tiersInfo['tiers_cpm']?? null;
        $result['tiers_names'] = $tiersInfo['tiers_names']?? null;

        $result['tiers_discounts'] = AdDiscountResource::collection(AdDiscount::where(function ($q){
            $q->whereNull('end_at')
                ->orWhere('end_at', '>', Carbon::now());
        })->get());

        return response()->json($result);
    }

    public function indexDiscount(Request $request)
    {
        $perPage = $request->get('per_page') ?: 15;

        $query = AdDiscount::query();

        $discounts = $query->paginate($perPage);

        return AdDiscountResource::collection($discounts);
    }
}
