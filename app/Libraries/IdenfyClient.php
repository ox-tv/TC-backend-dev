<?php


namespace App\Libraries;


use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IdenfyClient
{
    private $apiKey;
    private $apisecret;
    private $callbackSignature;
    private $apiUrl;

    public function __construct()
    {
        $this->apiKey = config("idenfy.api_key");
        $this->apisecret = config("idenfy.api_secret");
        $this->callbackSignature = config("idenfy.callback_signature");
        $this->apiUrl = config("idenfy.api_url");
    }

    public function ceateToken($clientId, $options = [])
    {
        $args = [
            'clientId' => $clientId,
            //'dummyStatus' => 'APPROVED'
        ];

        $args['firstName'] = $options['first_name'] ?? null;
        $args['lastName'] = $options['last_name'] ?? null;
        $args['successUrl'] = $options['success_url'] ?? null;
        $args['errorUrl'] = $options['error_url'] ?? null;
        $args['unverifiedUrl'] = $options['unverified_url'] ?? null;
        $args['callbackUrl'] = $options['webhook_url'] ?? null;
        $args['locale'] = $options['locale'] ?? 'en';

        try {
            $response = Http::withOptions([
                'auth' => [
                    $this->apiKey,
                    $this->apisecret
                ],
            ])->post("{$this->apiUrl}/token", $args);

            $body = $response->json();

            if(!$response->successful()){
                throw new Exception( "{$body['identifier']} - {$body['message']}", $response->status() );
            }

            $result['success'] = true;
            $result['data'] = $body;
            return $result;

        }catch(Exception $e){
            $result['success'] = false;
            $result['http_code'] = $e->getCode();
            $result['message'] = $e->getMessage();
            Log::error(print_r($result, true));
            return $result;
        }
    }

    public function createWebUiUrl($authToken)
    {
        return "https://ivs.idenfy.com/api/v2/redirect?authToken={$authToken}";
    }
}