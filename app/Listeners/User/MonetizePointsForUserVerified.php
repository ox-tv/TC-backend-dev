<?php

namespace App\Listeners\User;

use App\Events\UserVerified;
use App\Events\VideoViewed;
use App\Models\MonetizePoint;
use App\Models\UserMeta;
use App\Repository\Eloquent\MonetizePointRepository;
use Carbon\Carbon;

class MonetizePointsForUserVerified
{
    private $monetizePointRepository;

    public function __construct(MonetizePointRepository $monetizePointRepository)
    {
        $this->monetizePointRepository = $monetizePointRepository;
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
        $pointsPerReferral = config('points.monetize.per_referral');

        if (!$referrer){
            return 0;
        }

        $channel = $referrer->channel;
        if (!$channel){
            return 0;
        }

        $monetizeReferralPointsIsActive = $referrer->meta()->where('key', UserMeta::MonetizeReferralPointsIsActive)->first();

        $this->monetizePointRepository->add([
            'channel_id' => $channel->id,
            'activated_at' => $monetizeReferralPointsIsActive && $monetizeReferralPointsIsActive->value? Carbon::now() : null,
            'type' => MonetizePoint::TYPE_REFERRAL,
            'amount' => $pointsPerReferral,
            'monetization_multiplier' => $channel->monetization_multiplier,
        ], [
            'channel_id',
            'type',
            'date',
        ]);

        return;
    }
}
