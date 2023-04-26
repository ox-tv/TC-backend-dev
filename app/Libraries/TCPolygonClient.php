<?php


namespace App\Libraries;


use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TCPolygonClient
{
    private $baseUrl = 'http://localhost:3000';

    public function __construct()
    {

    }

    /*
     * Can pass multi symbols separated by comma
     * */
    public function getTCGBalance($walletAddress)
    {
        $result = [
            'success' => false,
            'message' => '',
            'balance' => null,
        ];

        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ])->get("{$this->baseUrl}/address/{$walletAddress}/balance");

            $body = $response->json();

            if(!$response->successful() || !$body['success']){
                throw new Exception(!empty($body['errorCode'])? $body['errorCode'] . ':' . $body['errorMessage'] : '', $response->status());
            }

            $result['success'] = true;
            $result['balance'] = $body['balance'];
            return $result;

        }catch(Exception $e){
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            Log::error($result['message']);
            return $result;
        }
    }

    public function ClimToken($destnation, $amount)
    {
        $result = [
            'success' => false,
            'message' => '',
            'hash' => null,
        ];

        $args = array(
            'to' => $destnation,
            'value' => $amount,
        );

        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ])->post("{$this->baseUrl}/send-transaction", $args);

            $body = $response->json();

            if(!$response->successful() || !$body['success']){
                throw new Exception(!empty($body['errorCode'])? $body['errorCode'] . ':' . $body['errorMessage'] : '', $response->status());
            }

            $result['success'] = true;
            $result['hash'] = $body['hash'];
            return $result;

        }catch(Exception $e){
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            Log::error($result['message']);
            return $result;
        }
    }
}