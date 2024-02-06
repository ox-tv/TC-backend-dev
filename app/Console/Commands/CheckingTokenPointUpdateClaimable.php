<?php

namespace App\Console\Commands;

use App\Libraries\CoinGeckoClient;
use App\Libraries\TCPolygonClient;
use App\Mail\GlobalMail;
use App\Models\CryptoCurrency;
use App\Models\TokenClaim;
use App\Models\TokenPoint;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

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

        \Artisan::call('tc:security:rate-limit:check-abuse-users --date=yesterday');

        $carbonNow = Carbon::now();
        $carbonStartOfDay = Carbon::now()->startOfDay();

        $tokenPoints = TokenPoint::raw(function($collection) use ($carbonStartOfDay) {
            return $collection->aggregate([
                ['$match' => [
                    'activate_at' => ['$lt'=> TokenPoint::fromDateTime($carbonStartOfDay)],
                    'claimable_at' => ['$eq'=> null],
                    'amount' => ['$gt'=> 0],
                ]],
                ['$group' => [
                    '_id' => '$user_id',
                    'user_id' => ['$last' => '$user_id'],
                    'points' => ['$sum' => '$amount'],
                ]],
                /*['$match' => [
                    '$or' => [['points' => ['$gte'=> 500]], ['user_id' => [ '$in' => TokenPoint::whereNotNull('claimable_at')->pluck('user_id')->toArray() ]]],
                ]],*/
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

        // Chunk and update claimable
        $countPerRequest = 1000;
        $max = count($addresses);

        for ($i = 0; $i < $max; $i += $countPerRequest ){
            $chunkAddresses = array_slice($addresses, $i, $countPerRequest);
            $chunkAmounts = array_slice($amounts, $i, $countPerRequest);
            $chunkUserIds = array_slice($finalUserIds, $i, $countPerRequest);

            $res = $polyganClient->updateClaimable($chunkAddresses, $chunkAmounts);

            if (!$res['success']){
                Mail::to(['robert@todayscrypto.com', 'aahelali@gmail.com'])
                    ->queue(new GlobalMail('Update Claimable Error', "Please check update claimable process!"));
                return 0;
            }

            TokenPoint::whereNull('claimable_at')
                ->where('activate_at', '<', $carbonStartOfDay)
                ->whereIn('user_id', $chunkUserIds)
                ->update([
                    'claimable_at' => TokenPoint::fromDateTime($carbonNow)
                ]);
        }



//        while (1){
//            $carbonNow = Carbon::now();
//            $carbonStartOfDay = Carbon::now()->startOfDay();
//
//            $tokenPoints = TokenPoint::raw(function($collection) use ($carbonStartOfDay) {
//                return $collection->aggregate([
//                    ['$match' => [
//                        'activate_at' => ['$lt'=> TokenPoint::fromDateTime($carbonStartOfDay)],
//                        'claimable_at' => ['$eq'=> null],
//                        'amount' => ['$gt'=> 0],
//                    ]],
//                    ['$group' => [
//                        '_id' => '$user_id',
//                        'user_id' => ['$last' => '$user_id'],
//                        'points' => ['$sum' => '$amount'],
//                    ]]/*,
//                    ['$match' => [
//                        '$or' => [['points' => ['$gte'=> 500]], ['user_id' => [ '$in' => TokenPoint::whereNotNull('claimable_at')->pluck('user_id')->toArray() ]]],
//                    ]]*/,
//                    [ '$limit' => 1000 ]
//                ]);
//            });
//
//            $addresses = [];
//            $amounts = [];
//            $finalUserIds = [];
//
//            foreach ($tokenPoints as $tokenPoint){
//                $wallet = $tokenPoint->user->auth_wallet ?? null;
//
//                if (!$wallet){
//                    continue;
//                }
//
//                $addresses[] = $wallet;
//                $amounts[] = $tokenPoint->points;
//                $finalUserIds[] = $tokenPoint->user_id;
//            }
//
//            if (empty($finalUserIds)){
//                break;
//            }
//
//            $res = $polyganClient->updateClaimable($addresses, $amounts);
//
//            if (!$res['success']){
//                Mail::to(['robert@todayscrypto.com', 'aahelali@gmail.com'])
//                    ->queue(new GlobalMail('Update Claimable Error', "Please check update claimable process!"));
//                break;
//            }
//
//            TokenPoint::whereNull('claimable_at')
//                ->where('activate_at', '<', $carbonStartOfDay)
//                ->whereIn('user_id', $finalUserIds)
//                ->update([
//                    'claimable_at' => TokenPoint::fromDateTime($carbonNow)
//                ]);
//        }

        return 0;
    }
}
