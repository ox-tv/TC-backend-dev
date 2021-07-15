<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanStore;
use App\Http\Requests\PlanUpdate;
use App\Http\Resources\Plan\PlanItem;
use App\Models\Plan;
use App\Models\Pricing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $query = Plan::with(['pricing','pricing.paymentMethod']);

        if($request->is('api/plans')){
            $query->where('status', Plan::STATUS_ACTIVE);
        }

        $plans = $query->get();

        return PlanItem::collection($plans);
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

            if($request->get('pricing')){
                foreach ($request->get('pricing') as $p){
                    $pricing = new Pricing();
                    $pricing->plan_id = $plan->id;
                    $pricing->payment_method_id = $p['payment_method_id'];
                    $pricing->external_id = $p['external_id'];
                    $pricing->amount = $p['amount'];
                    $pricing->currency = $p['currency'];

                    $pricing->save();
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

            if($request->get('pricing')){

                $existingIds = [];

                foreach ($request->get('pricing') as $p){

                    if(!empty($p['id'])){
                        $pricing = Pricing::find($p['id']);
                    }else{
                        $pricing = new Pricing();
                        $pricing->plan_id = $plan->id;
                    }

                    $pricing->payment_method_id = $p['payment_method_id'];
                    $pricing->external_id = $p['external_id'];
                    $pricing->amount = $p['amount'];
                    $pricing->currency = $p['currency'];

                    $pricing->save();

                    $existingIds[] = $pricing->id;
                }

                Pricing::whereNotIn('id', $existingIds)->delete();
            }

        });

        return response()->json(['message' => 'ok']);
    }

    public function destroy(Plan $plan)
    {
        Pricing::where('plan_id', $plan->id)->delete();
        $plan->delete();

        return response()->json(['message' => 'ok']);
    }
}
