<?php


namespace App\Libraries;


use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TCPolygonClient
{
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.tc_polygon.base_url');
    }

    /*
     * Can pass multi symbols separated by comma
     * */
    public function getNFTsByOwner($walletAddress)
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
            ])->get("{$this->baseUrl}/nft/{$walletAddress}");

            $body = $response->json();

            if(!$response->successful()){
                throw new Exception(!empty($body['errorCode'])? $body['errorCode'] . ':' . $body['errorMessage'] : '', $response->status());
            }

            $result['success'] = true;
            $result['data'] = $body;
            return $result;

        }catch(Exception $e){
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            Log::error($result['message']);
            return $result;
        }
    }

    public function sendTransaction($destnation, $amount)
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

    public function updateClaimable($addresses, $amounts)
    {
        $result = [
            'success' => false,
            'message' => '',
            'hash' => null,
        ];

        $args = array(
            'users' => $addresses,
            'amounts' => array_map('strval', $amounts),
        );

        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ])->post("{$this->baseUrl}/update-claimable", $args);

            $body = $response->json();

            if(!$response->successful() || !$body['success']){
                throw new Exception(!empty($body['errorCode'])? $body['errorCode'] . ':' . $body['errorMessage'] : '', $response->status());
            }

            $result['success'] = true;
            return $result;

        }catch(Exception $e){
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            Log::error($result['message']);
            return $result;
        }
    }
}