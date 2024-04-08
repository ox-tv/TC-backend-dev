<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Models\UserStatisticsDaily;
use Carbon\Carbon;

class UserVerifiedDataForUserStatisticsDaily
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function handle(UserVerified $event)
    {
        $user = $event->user;
        $referrer = $user->referrer;

        if (!$referrer){
            return;
        }

        // Add +1 to user statistics
        $statistics = UserStatisticsDaily::firstOrNew([
            'user_id' => $referrer->id,
            'date' => Carbon::now()->startOfDay(),
        ]);

        $statistics->referral_count_total += 1;

        if($referrer->is_hero){
            $statistics->referral_count_as_hero += 1;
        }else{
            $statistics->referral_count_as_non_hero += 1;
        }

        $statistics->calcPoints();

        $statistics->save();

        return $statistics;
    }
}
