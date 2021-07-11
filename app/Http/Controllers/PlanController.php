<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanStore;
use App\Http\Requests\PlanUpdate;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::all();

        return $plans;
    }

    public function store(PlanStore $request)
    {
        $plan = new Plan();

        $plan->name = $request->get('name');
        $plan->description = $request->get('description');
        $plan->interval = $request->get('interval');
        $plan->status = array_flip(Plan::STATUS_TEXT)[$request->get('status')];
        $plan->thumbnail = $request->get('thumbnail');


        // Save to DB
        DB::transaction(function () use ($plan, $request){

            $plan->save();

            if($request->get('rates')){
                foreach ($request->get('rates') as $rate){
                    $plan->paymentMethods()->attach($rate['payment_method_id'], [
                        'external_id' => $rate['external_id'],
                        'amount' => $rate['amount'],
                        'currency' => $rate['currency'],
                    ]);
                }
            }
        });

        return response()->json(['message' => 'ok']);
    }

    public function update(PlanUpdate $request, Plan $plan)
    {
        $plan->name = $request->get('name');
        $plan->description = $request->get('description');
        $plan->interval = $request->get('interval');
        $plan->status = array_flip(Plan::STATUS_TEXT)[$request->get('status')];
        $plan->thumbnail = $request->get('thumbnail');


        // Save to DB
        DB::transaction(function () use ($plan, $request){

            $plan->save();
            $plan->paymentMethods()->detach();

            if($request->get('rates')){
                foreach ($request->get('rates') as $rate){
                    $plan->paymentMethods()->attach($rate['payment_method_id'], [
                        'external_id' => $rate['external_id'],
                        'amount' => $rate['amount'],
                        'currency' => $rate['currency'],
                    ]);
                }
            }
        });

        return response()->json(['message' => 'ok']);
    }

    public function destroy(Plan $plan)
    {
        $plan->paymentMethods()->detach();
        $plan->delete();

        return response()->json(['message' => 'ok']);
    }
}
