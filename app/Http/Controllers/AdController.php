<?php

namespace App\Http\Controllers;

use App\Http\Resources\Ad\AdCampaignResource;
use App\Http\Resources\Ad\AdPricingResource;
use App\Http\Resources\Ad\AdSlotResource;
use App\Models\AdCampaign;
use App\Models\AdPricing;
use App\Models\AdSlot;
use App\Models\Option;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AdController extends Controller
{
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

        foreach ($request->get('slots') as $row){
            $slot = new AdSlot();
            $slot->ad_campaign_id = $campaign->id;
            $slot->date = $row['date'];
            $slot->tier = $row['tier'];
            $slot->quantity = $row['quantity'];
            $slot->price = 100;
            $slot->save();
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

            $preExistingSlotIds = array_column($request->get('slots'), 'id');
            AdSlot::where('ad_campaign_id', $campaign->id)->whereNotIn('id', $preExistingSlotIds)->delete();

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
            'tiers_names' => 'required',
            'prices' => 'nullable',
            'prices.*.date' => 'required|date_format:Y-m-d',
            'prices.*.tier' => 'required|in:1,2,3,4,5',
            'prices.*.price' => 'required|numeric',
        ]);

        Option::set(Option::AD_TIERS_NAMES, json_encode($request->get('tiers_names')));

        if ($prices = $request->get('prices')){
            foreach ($prices as $row){
                AdPricing::updateOrCreate(
                    ['date' => $row['date'], 'tier' => $row['tier']],
                    ['price' => $row['price']]
                );
            }
        }

        return response()->json(["message" => "ok"]);
    }

    public function getSettings(Request $request)
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

        $prices = AdPricing::where('date', '>=', $from)->where('date', '<=', $to)->get();

        $tierNames = Option::get(Option::AD_TIERS_NAMES)->value ?? null;
        $tierNames = $tierNames? json_decode($tierNames, true): null;

        return response()->json(['prices' => AdPricingResource::collection($prices), 'tier_names' => $tierNames]);
    }
}
