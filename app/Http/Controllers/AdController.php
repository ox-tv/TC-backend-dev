<?php

namespace App\Http\Controllers;

use App\Http\Resources\Form\FormResource;
use App\Mail\GlobalMail;
use App\Models\AdPricing;
use App\Models\Form;
use App\Models\Option;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class AdController extends Controller
{


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
            'from' => ['nullable','date_format:Y-m-d'],
            'to' => ['nullable','date_format:Y-m-d', 'after:from'],
        ]);

        $from = $request->get('from')? Carbon::parse($request->get('from')) : Carbon::now();
        $to = $request->get('to')? Carbon::parse($request->get('to')) : Carbon::now()->addDays(14);

        $prices = AdPricing::where('date', '>=', $from)->where('date', '<=', $to)->get();

        $tierNames = Option::get(Option::AD_TIERS_NAMES)->value ?? null;
        $tierNames = $tierNames? json_decode($tierNames, true): null;

        return response()->json(['prices' => $prices, 'tier_names' => $tierNames]);
    }
}
