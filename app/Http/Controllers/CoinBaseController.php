<?php

namespace App\Http\Controllers;

use App\Http\Resources\CryptoCurrency\CryptoCurrencyItem;
use App\Libraries\CoinBaseClient;
use App\Libraries\CoinMarketCapClient;
use App\Models\Category;
use App\Models\CryptoCurrency;
use App\Models\Plan;
use App\Models\Pricing;
use App\Models\User;
use App\Repository\Eloquent\PricingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use function Illuminate\Support\Facades\Log;

class CoinBaseController extends Controller
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
        $event_data = $data['event']['data'];

        Log::info( 'Webhook received event: ' . print_r( $data, true ) );

        if ( ! isset( $event_data['metadata']['source'] ) ) {
            // Probably a charge not created by us.
            exit;
        }

        switch ($event_data['metadata']['source']){
            case 'hero_membership':
                $this->heroMembershipWebHookHandler($client, $event_data);
                break;
            default:
        }

        exit;  // 200 response for acknowledgement.
    }

    public function heroMembershipWebHookHandler($client, $event_data)
    {
        if ( !isset( $event_data['metadata']['pricing_id'] ) || !isset( $event_data['metadata']['user_id'] ) ) {
            // Probably a charge not created by us.
            exit;
        }

        $pricing = Pricing::find($event_data['metadata']['pricing_id']);
        $user = User::find($event_data['metadata']['user_id']);

        $timeline = $event_data['timeline'];
        $last_update = end( $timeline );
        $last_status = $last_update['status'];





        $pricingRepository = new PricingRepository();
        $pricingRepository->addPricingToUser($user, $pricing);

        return response()->json(['message' => 'ok']);
    }


    public function _update_order_status( $order, $timeline ) {
        $prev_status = $order->get_meta( '_coinbase_status' );

        $last_update = end( $timeline );
        $status      = $last_update['status'];
        if ( $status !== $prev_status ) {
            $order->update_meta_data( '_coinbase_status', $status );

            if ( 'EXPIRED' === $status && 'pending' == $order->get_status() ) {
                $order->update_status( 'cancelled', __( 'Coinbase payment expired.', 'coinbase' ) );
            } elseif ( 'CANCELED' === $status ) {
                $order->update_status( 'cancelled', __( 'Coinbase payment cancelled.', 'coinbase' ) );
            } elseif ( 'UNRESOLVED' === $status ) {
                if ($last_update['context'] === 'OVERPAID') {
                    $order->update_status( 'processing', __( 'Coinbase payment was successfully processed.', 'coinbase' ) );
                    $order->payment_complete();
                } else {
                    // translators: Coinbase error status for "unresolved" payment. Includes error status.
                    $order->update_status( 'failed', sprintf( __( 'Coinbase payment unresolved, reason: %s.', 'coinbase' ), $last_update['context'] ) );
                }
            } elseif ( 'PENDING' === $status ) {
                $order->update_status( 'blockchainpending', __( 'Coinbase payment detected, but awaiting blockchain confirmation.', 'coinbase' ) );
            } elseif ( 'RESOLVED' === $status ) {
                // We don't know the resolution, so don't change order status.
                $order->add_order_note( __( 'Coinbase payment marked as resolved.', 'coinbase' ) );
            } elseif ( 'COMPLETED' === $status ) {
                $order->update_status( 'processing', __( 'Coinbase payment was successfully processed.', 'coinbase' ) );
                $order->payment_complete();
            }
        }

        // Archive if in a resolved state and idle more than timeout.
        if ( in_array( $status, array( 'EXPIRED', 'COMPLETED', 'RESOLVED' ), true ) &&
            $order->get_date_modified() < $this->timeout ) {
            self::log( 'Archiving order: ' . $order->get_order_number() );
            $order->update_meta_data( '_coinbase_archived', true );
        }
    }
}
