<?php

namespace App\Http\Controllers;

use App\Http\Resources\CryptoCurrency\CryptoCurrencyItem;
use App\Libraries\CoinBaseClient;
use App\Libraries\CoinMarketCapClient;
use App\Models\Category;
use App\Models\CryptoCurrency;
use App\Models\Plan;
use App\Models\Pricing;
use App\Models\PricingUser;
use App\Models\Transaction;
use App\Models\User;
use App\Repository\Eloquent\PricingRepository;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoinbaseController extends Controller
{
    public function webHookHandler(Request $request)
    {
        $payload = file_get_contents( 'php://input' );
        $client = new CoinBaseClient();

        if ( empty( $payload ) || $client->validateWebhook( $payload ) ) {
            abort(500, 'Coinbase Webhook Request Failure');
            //wp_die( 'Coinbase Webhook Request Failure', 'Coinbase Webhook', array( 'response' => 500 ) );
        }

        $data       = json_decode( $payload, true );
        $eventData = $data['event']['data'];

        Log::info( 'Webhook received event: ', ['data' => $data] );

        if ( ! isset( $eventData['metadata']['source'] ) ) {
            // Probably a charge not created by us.
            return response()->json(['status' => 'ok']);
        }

        switch ($eventData['metadata']['source']){
            case 'hero_membership':
                $this->heroMembershipWebHookHandler($client, $eventData);
                break;
            default:
        }

        return response()->json(['status' => 'ok']);  // 200 response for acknowledgement.
    }

    public function heroMembershipWebHookHandler($client, $eventData)
    {
        if ( !isset( $eventData['metadata']['pricing_user_id'] ) ) {
            // Probably a charge not created by us.
            return response()->json(['status' => 'ok']);
        }

        $pricingUser = PricingUser::find($eventData['metadata']['pricing_user_id']);
        $transaction = $pricingUser->transaction;
        $metadata = $pricingUser->metadata;
        $prevStatus = $metadata['coinbase_status']?? 'NEW';

        $user = User::find($pricingUser->user_id);

        $timeline = $eventData['timeline'];
        $lastUpdate = end( $timeline );
        $lastStatus = $lastUpdate['status'];

        if ( $lastStatus !== $prevStatus ) {
            $metadata['coinbase_status'] = $lastStatus;
            $pricingUser->metadata = $metadata;

            $transactionChangeFlag = false;

            if ( 'EXPIRED' === $lastStatus && $pricingUser->status == PricingUser::STATUS_PENDING ) {
                $transaction->status = Transaction::STATUS_FAILED;
                $pricingUser->status = PricingUser::STATUS_CANCELED;
                $transactionChangeFlag = true;
            } elseif ( 'CANCELED' === $lastStatus ) {
                $transaction->status = Transaction::STATUS_FAILED;
                $pricingUser->status = PricingUser::STATUS_CANCELED;
                $transactionChangeFlag = true;
            } elseif ( 'UNRESOLVED' === $lastStatus ) {
                if ($lastUpdate['context'] === 'OVERPAID') {
                    $transaction->status = Transaction::STATUS_COMPLETED;
                    $pricingUser->status = PricingUser::STATUS_COMPLETED;
                    $transactionChangeFlag = true;
                } else {
                    // translators: Coinbase error status for "unresolved" payment. Includes error status.
                    $transaction->status = Transaction::STATUS_FAILED;
                    $pricingUser->status = PricingUser::STATUS_FAILED;
                    $transactionChangeFlag = true;
                }
            } elseif ( 'PENDING' === $lastStatus ) {
                $pricingUser->status = PricingUser::STATUS_PENDING_BLOCKCHAIN;
            } elseif ( 'RESOLVED' === $lastStatus ) {
                // We don't know the resolution, so don't change order status.
            } elseif ( 'COMPLETED' === $lastStatus ) {
                $transaction->status = Transaction::STATUS_COMPLETED;
                $pricingUser->status = PricingUser::STATUS_COMPLETED;
                $transactionChangeFlag = true;
            }

            DB::transaction(function () use ($pricingUser, $transaction, $transactionChangeFlag, $user){
                $plan = $pricingUser->pricing->plan;

                $pricingUser->save();

                if ($transactionChangeFlag){
                    $transaction->save();
                }

                if ($pricingUser->status = PricingUser::STATUS_COMPLETED){
                    if ($user->hero_due_at && $user->hero_due_at > Carbon::now()){
                        $user->hero_due_at = $user->hero_due_at->addDays($plan->interval);
                    }else{
                        $user->hero_due_at = Carbon::now()->addDays($plan->interval);
                    }
                    $user->save();
                }
            });
        }

        return true;
    }

}
