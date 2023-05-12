<?php

namespace App\Console\Commands;

use App\Libraries\CoinGeckoClient;
use App\Libraries\TCPolygonClient;
use App\Models\CryptoCurrency;
use App\Models\TokenClaim;
use App\Models\TokenPoint;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckingTokenPointUpdateClaimable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tcg:update-claimable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update claimable.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $polyganClient = new TCPolygonClient();
        $carbonNow = Carbon::now();

        $tokenPoints = TokenPoint::raw(function($collection) use ($carbonNow) {
            return $collection->aggregate([
                ['$match' => [
                    'activate_at' => ['$lte'=> TokenPoint::fromDateTime($carbonNow)],
                    'claimable_at' => ['$eq'=> null],
                ]],
                ['$group' => [
                    '_id' => '$user_id',
                    'user_id' => ['$last' => '$user_id'],
                    'points' => ['$sum' => '$amount'],
                ]],
                ['$match' => [
                    'points' => ['$gte'=> 500],
                ]],
            ]);
        });

        $addresses = [];
        $amounts = [];
        $finalUserIds = [];

        foreach ($tokenPoints as $tokenPoint){
            $wallet = $tokenPoint->user->auth_wallet ?? null;

            if (!$wallet){
                continue;
            }

            $addresses[] = $wallet;
            $amounts[] = $tokenPoint->points;
            $finalUserIds[] = $tokenPoint->user_id;
        }

        if (empty($finalUserIds)){
            return 0;
        }

        $res = $polyganClient->updateClaimable($addresses, $amounts);

        if (!$res['status']){
            return 0;
        }

        TokenPoint::whereNull('claimable_at')
        ->where('activate_at', '<=', $carbonNow)
        ->whereIn('user_id', $finalUserIds)
        ->update([
            'claimable_at' => TokenPoint::fromDateTime($carbonNow)
        ]);

        return 0;
    }
}
