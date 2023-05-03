<?php

namespace App\Console\Commands;

use App\Libraries\CoinGeckoClient;
use App\Libraries\TCPolygonClient;
use App\Models\CryptoCurrency;
use App\Models\TokenClaim;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DoingTokenClaimRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:do-claim-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Doing claim requests.';

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
        if (Cache::has('delay_token_do_claim_requests')){
            return 0;
        }

        $polyganClient = new TCPolygonClient();
        $requests = TokenClaim::where('status', TokenClaim::STATUS_PENDING)->get();

        foreach ($requests as $request){
            $res = $polyganClient->ClaimToken($request->destination, strval($request->amount));

            if (!$res['status']){
                Cache::put('delay_token_do_claim_requests', true, 10 * 60);
                break;
            }

            $request->data = ['hash' => $res['hash']];
            $request->status = TokenClaim::STATUS_SUCCESS;
            $request->executed_at = Carbon::now();
            $request->save();
        }

        return 0;
    }
}
