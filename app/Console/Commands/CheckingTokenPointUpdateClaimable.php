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
        $tokenPoints = TokenPoint::raw(function($collection) {
            return $collection->aggregate([
                ['$match' => [
                    'activate_at' => ['$lte'=> TokenPoint::fromDateTime(Carbon::now())],
                    'claimable_at' => ['$eq'=> null],
                ]],
                ['$group' => [
                    '_id' => '$user_id',
                    'user_id' => ['$last' => '$user_id'],
                    'points' => ['$sum' => '$amount'],
                ]],
                ['$match' => [
                    'points' => ['$gte'=> 3],
                ]],
            ]);
        });

        $data = [];

        foreach ($tokenPoints as $tokenPoint){
            $wallet = $tokenPoint->user->auth_wallet ?? null;

            if (!$wallet){
                continue;
            }

            $data[$wallet] = !empty($data[$wallet])? $data[$wallet] + $tokenPoint->points : $tokenPoint->points;
        }


        $addresses = array_keys($data);
        $amounts = array_values($data);

        $res = $polyganClient->updateClaimable($addresses, $amounts);

        return 0;
    }
}
