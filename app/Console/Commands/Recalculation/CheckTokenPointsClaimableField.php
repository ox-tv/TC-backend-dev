<?php

namespace App\Console\Commands\Recalculation;

use App\Models\PricingUser;
use App\Models\TokenPoint;
use App\Models\User;
use App\Repository\Eloquent\LoyaltyPointRepository;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckTokenPointsClaimableField extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:token-points:modify-claimable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Modify claimable in token points';

    /**
     * Execute the console command.
     *
     * @return int
     */
    private $tokenPointRepository;

    public function handle()
    {
        $userIds = User::whereNull('auth_wallet')
            ->where('status', User::STATUS_ACTIVE)
            ->pluck('id')->toArray();


        $userIds2 = TokenPoint::raw(function($collection) {
            return $collection->aggregate([
                ['$match' => [
                    'activate_at' => ['$lt'=> TokenPoint::fromDateTime(Carbon::parse('2024-01-09'))],
                    'amount' => ['$gt'=> 0],
                ]],
                ['$group' => [
                    '_id' => '$user_id',
                    'user_id' => ['$last' => '$user_id'],
                    'points' => ['$sum' => '$amount'],
                ]],
                ['$match' => [
                    'points' => ['$lt'=> 500],
                ]]
            ]);
        })->pluck('user_id')->toArray();

        $userIds3 = [66300];

        $totalUserIds = array_merge($userIds, $userIds2, $userIds3);

        dump(count($userIds), count($userIds2), count($totalUserIds));

        TokenPoint::whereIn('user_id', $totalUserIds)
            ->where('claimable_by', 'FakeByReCalculate')
            ->update([
                'claimable_at' => null,
                'claimable_by' => null,
            ]);

        return 0;
    }


}
