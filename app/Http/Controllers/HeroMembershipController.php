<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Pricing;
use App\Models\User;
use App\Repository\PricingRepositoryInterface;
use Illuminate\Http\Request;

class HeroMembershipController extends Controller
{
    private $pricingRepository;

    public function __construct(PricingRepositoryInterface $pricingRepository)
    {
        $this->pricingRepository = $pricingRepository;
    }


    public function store(Request $request, Pricing $pricing)
    {
        $exists = $pricing->plan()->where('status', Plan::STATUS_ACTIVE)->exists();

        abort_unless($exists, 404);

        if($request->is('api/admin/*')){
            $user = User::findOrFail($request->get('user_id'));
        }else{
            $user = auth('api')->user();
        }

        $this->pricingRepository->addPricingToUser($user, $pricing);

        return response()->json(['message' => 'ok']);
    }
}
