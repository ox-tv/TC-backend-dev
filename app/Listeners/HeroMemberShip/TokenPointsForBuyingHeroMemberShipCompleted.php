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
        $pricingUser = $event->pricingUser;
        $plan = $pricingUser->pricing->plan;

        // Check conditions
        if ($plan->interval < 360){
            return true;
        }

        $this->tokenPointRepository->add([
            'user_id' => $user->id,
            'type' => $user->is_hero? TokenPoint::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP_AS_HERO : TokenPoint::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP,
            'amount' => $user->is_hero? config('points.token.buying_yearly_membership_as_hero') : config('points.token.buying_yearly_membership'),
        ]);

        return true;
    }
}
