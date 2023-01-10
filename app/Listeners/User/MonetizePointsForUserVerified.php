<?php

namespace App\Listeners\User;

use Amir\Permission\Models\Role;
use App\Events\UserVerified;
use App\Events\VideoViewed;
use App\Models\MonetizePoint;
use App\Models\User;
use App\Models\UserStatisticsDaily;
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

        if (!($channel = $referrer->channel)){
            return 0;
        }

        return $this->monetizePointRepository->add([
            'channel_id' => $channel->id,
            'type' => MonetizePoint::TYPE_REFERRAL,
            'amount' => $pointsPerReferral,
        ], [
            'channel_id',
            'type',
            'date',
        ]);
    }
}
