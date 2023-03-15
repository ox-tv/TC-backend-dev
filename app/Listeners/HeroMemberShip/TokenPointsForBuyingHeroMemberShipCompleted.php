<?php

namespace App\Listeners\HeroMemberShip;

use App\Events\User\BuyingHeroMemberShipCompleted;
use App\Events\User\CustomFeedFilled;
use App\Models\TokenPoint;
use App\Repository\Eloquent\TokenPointRepository;

class TokenPointsForBuyingHeroMemberShipCompleted
{
    private $tokenPointRepository;

    public function __construct(TokenPointRepository $tokenPointRepository)
    {
        $this->tokenPointRepository = $tokenPointRepository;
    }

    public function handle(BuyingHeroMemberShipCompleted $event)
    {
        $user = $event->user;
        $pricingUser = $this->pricingUser;
        $plan = $pricingUser->pricing->plan;

        // Check conditions
        if ($plan->interval < 360){
            return true;
        }

        $amount = $user->is_hero? config('points.token.buying_yearly_membership_as_hero') : config('points.token.buying_yearly_membership');

        $this->tokenPointRepository->add([
            'user_id' => $user->id,
            'type' => TokenPoint::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP,
            'amount' => $amount,
        ]);

        return true;
    }
}
