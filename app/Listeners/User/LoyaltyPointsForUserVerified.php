<?php

namespace App\Listeners\User;

use App\Events\UserVerified;
use App\Events\VideoViewed;
use App\Models\LoyaltyPoint;
use App\Repository\Eloquent\LoyaltyPointRepository;
use Carbon\Carbon;

class LoyaltyPointsForUserVerified
{
    private $loyaltyPointRepository;

    public function __construct(LoyaltyPointRepository $loyaltyPointRepository)
    {
        $this->loyaltyPointRepository = $loyaltyPointRepository;
    }

    /**
     * Handle the event.
     *
     * @param  VideoViewed  $event
     * @return void
     */
    public function handle(UserVerified $event)
    {
        $user = $event->user;
        $referrer = $user->referrer;

        if (!$referrer){
            return 0;
        }

        // Points for new user
        $pointsReferral = config('points.loyalty.referral');

        $this->loyaltyPointRepository->add([
            'user_id' => $user->id,
            'activated_at' => Carbon::now(),
            'type' => LoyaltyPoint::TYPE_REFERRAL,
            'amount' => $pointsReferral,
        ]);


        // Points for referrer (not publisher)
        $channel = $referrer->channel;
        if ($channel){
            return 0;
        }

        if ($user->is_hero){
            $pointsReferrer = config('points.loyalty.per_referrer_as_hero');
        }else{
            $pointsReferrer = config('points.loyalty.per_referrer_as_non_hero');
        }

        $this->loyaltyPointRepository->add([
            'user_id' => $referrer->id,
            'activated_at' => Carbon::now(),
            'type' => LoyaltyPoint::TYPE_REFERRER,
            'amount' => $pointsReferrer,
        ]);

        return 0;
    }
}
