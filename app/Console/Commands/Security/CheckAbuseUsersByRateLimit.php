<?php

namespace App\Console\Commands\Security;

use App\Models\PaymentDetails;
use App\Models\SecurityRateLimit;
use App\Models\TokenPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckAbuseUsersByRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:security:ratelimit:check-abuse-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check abuse users by rate limit';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Unclaimable tokens if users has more than 100 blocked requests
        $rows = SecurityRateLimit::raw(function($collection){
            return $collection->aggregate([
                ['$group' => [
                    '_id' => '$user_id',
                    'count' => [
                        '$sum' => 1
                    ],
                ]],
                ['$match' => [
                    'count' => ['$gte'=> 100],
                ]],
                ['$sort' => ['count' => -1]],
            ]);
        })->pluck('_id')->toArray();

        $userIds = array_map(function($user_id){ return intval($user_id); }, $rows);
        $carbonNow = Carbon::now();

        TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime($carbonNow), 'claimable_by' => 'security.rate_limit']);


        // Set status to inactive if users has more than 800 blocked requests
        $rows = SecurityRateLimit::raw(function($collection){
            return $collection->aggregate([
                ['$group' => [
                    '_id' => '$user_id',
                    'count' => [
                        '$sum' => 1
                    ],
                ]],
                ['$match' => [
                    'count' => ['$gte'=> 500],
                ]],
                ['$sort' => ['count' => -1]],
            ]);
        })->pluck('_id')->toArray();

        $userIds = array_map(function($user_id){ return intval($user_id); }, $rows);

        User::whereIn('id', $userIds)->update(['status' => User::STATUS_INACTIVE]);

        return 0;
    }
}
