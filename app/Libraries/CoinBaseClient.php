<?php


namespace App\Libraries;


use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoinBaseClient
{
    private $apiKey;
    private $webhookSecret;
    private $apiVersion = '2018-03-22';
    private $baseUrl = 'https://api.commerce.coinbase.com';
    public $timeout;

    public function __construct()
    {
        $this->apiKey = config("general.coinbase.api_key");
        $this->webhookSecret = config("general.coinbase.webhook_secret");
        $this->apiVersion = config("general.coinbase.api_version");
        $this->baseUrl = config("general.coinbase.base_url");
        $this->timeout = Carbon::now()->subDays(3);
    }

    /*
     * Can pass multi symbols separated by comma
     * */
    public function createCharge( $name, $desc, $amount = null, $currency = null, $metadata = null, $redirect = null, $cancel = null)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null,
        ];

        $args = array(
            'name'        => $name,
            'description' => $desc,
        );

        if ( is_null( $amount ) ) {
            $args['pricing_type'] = 'no_price';
        } elseif ( is_null( $currency ) ) {
            $result['success'] = false;
            $result['message'] = 'if amount is given, currency must be given.';
            return $result;
        } else {
            $args['pricing_type'] = 'fixed_price';
            $args['local_price']  = array(
                'amount'   => $amount,
                'currency' => $currency,
            );
        }

        if ( ! is_null( $metadata ) ) {
            $args['metadata'] = $metadata;
        }
        if ( ! is_null( $redirect ) ) {
            $args['redirect_url'] = $redirect;
        }
        if ( ! is_null( $cancel ) ) {
            $args['cancel_url'] = $cancel;
        }

        // send request
        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'X-CC-Api-Key' => $this->apiKey,
                    'X-CC-Version' => $this->apiVersion,
                    'Content-Type' => 'application/json',
                ]
            ])->post("{$this->baseUrl}/charges", $args);

            $body = $response->json();

            if ( ! empty( $body['warnings'] ) ) {
                foreach ( $body['warnings'] as $warning ) {
                    Log::warning( 'CoinBase API Warning: ' . $warning );
                }
            }

            if(!$response->successful()){
                throw new Exception(
                    empty( $body['error']['message'] ) ? '' : $body['error']['message'],
                    $response->status()
                );
            }

            $result['success'] = true;
            $result['data'] = $body['data'];
            return $result;

        }catch(Exception $e){
            $result['success'] = false;
            $result['message'] = $this->errorHandler($e->getCode(), $e->getMessage());
            Log::error($result['message']);
            return $result;
        }
    }

    public function validateWebhook($payload)
    {
        $secret = $this->webhookSecret;
        $signature = request()->header('X-X-CC-Webhook-Signature-Name');

        if ( !$signature ) {
            return false;
        }

        $signature2 = hash_hmac( 'sha256', $payload, $secret );

        if ( $signature === $signature2 ) {
            return true;
        }

        return false;
    }

    private function errorHandler($errorCode, $responseMessage = '')
    {
        switch($errorCode) {
            case 400:
                $message = 'Error response from API: ' . $responseMessage;
                break;
            case 401:
                $message = 'Authentication error, please check your API key.';
                break;
            case 429:
                $message = 'Coinbase API rate limit exceeded.';
                break;
            default:
                $message = 'Unknown response from API: ' . $errorCode;
        }

        return $message;
    }
}