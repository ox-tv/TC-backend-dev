<?php

namespace App\Console\Commands\Security;

use App\Models\PaymentDetails;
use App\Models\SecurityRateLimit;
use App\Models\TokenPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckAbuseUsersByRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:security:rate-limit:check-abuse-users {--date=}';

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
        $date = Carbon::now()->format('Y-m-d');
        if ($this->option('date') == 'yesterday'){
            $date = Carbon::now()->subDay()->format('Y-m-d');
        }


        $ipAddresses = (new SecurityRateLimit())
            ->setCollection("rate_limit_{$date}")->raw(function($collection){
                return $collection->aggregate([
                    ['$group' => ['_id' => ['ip_address' => '$ip_address', 'user_id' => '$user_id'],]],
                    ['$group' => ['_id' => '$_id.ip_address',"users_count" => ['$sum' => 1] ]],
                    ['$match' => ['users_count' => ['$gt'=> 5],]],
                    ['$sort' => ['users_count' => -1]]
                ]);
            })->pluck('_id')->toArray();

        $userIds = (new SecurityRateLimit())
            ->setCollection("rate_limit_{$date}")
            ->raw(function($collection) use ($ipAddresses){
                return $collection->aggregate([
                    ['$match' => ['ip_address' => ['$in' => $ipAddresses]]],
                    ['$group' => ['_id' => '$user_id']]
                ]);
            })->pluck('_id')->filter()->toArray();


        $userIds = array_map(function($user_id){ return intval($user_id); }, $userIds);
        //$ipAddresses = array_merge($ipAddresses, User::whereIn('id', $userIds)->pluck('last_active_from_ip')->toArray());

        foreach ($ipAddresses as $ipAddress){
            Cache::put("\App\Http\Controllers\VideoController@watch_time_store.ip{$ipAddress}.block", true, Carbon::now()->addDays(1));
            Cache::put("\App\Http\Controllers\Auth\Web3LoginController@login.ip{$ipAddress}.block", true, Carbon::now()->addDays(1));
            Cache::put("\App\Http\Controllers\Auth\RegisterController@register.ip{$ipAddress}.block", true, Carbon::now()->addDays(1));
        }

        TokenPoint::whereIn('user_id', $userIds)->whereNull('claimable_at')->update(['claimable_at' => TokenPoint::fromDateTime(Carbon::now()), 'claimable_by' => 'security.rate_limit']);
        User::whereIn('id', $userIds)->update(['status' => User::STATUS_INACTIVE]);

        return 0;
    }
}
