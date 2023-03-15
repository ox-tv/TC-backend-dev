<?php

namespace App\Listeners\User;

use App\Events\UserVerified;
use App\Events\VideoViewed;
use App\Models\MonetizePoint;
use App\Models\TokenPoint;
use App\Models\UserMeta;
use App\Repository\Eloquent\MonetizePointRepository;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;

class TokenPointsForUserVerified
{
    private $tokenPointRepository;

    public function __construct(TokenPointRepository $tokenPointRepository)
    {
        $this->tokenPointRepository = $tokenPointRepository;
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
            return true;
        }

        if ($referrer->channel){
            $this->tokenPointRepository->add([
                'user_id' => $referrer->id,
                'type' => TokenPoint::TYPE_REFERRER_AS_PUBLISHER,
                'amount' => config('points.token.referrer_as_publisher'),
            ]);
            $this->tokenPointRepository->add([
                'user_id' => $user->id,
                'type' => TokenPoint::TYPE_REFERRAL_VIA_PUBLISHER,
                'amount' => config('points.token.referral_via_publisher'),
            ]);
        }else{
            $amount = $referrer->is_hero? config('points.token.referrer_as_hero') : config('points.token.referrer');
            $this->tokenPointRepository->add([
                'user_id' => $referrer->id,
                'type' => TokenPoint::TYPE_REFERRER,
                'amount' => $amount,
            ]);
        }

        return true;
    }
}
