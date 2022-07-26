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
            'clientId' => $clientId
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
            $result['success'] = true;
            $result['data'] = $body;
            return $result;

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
            $result['message'] = $e->getCode() . $e->getMessage();
            Log::error($result['message']);
            return $result;
        }
    }
}