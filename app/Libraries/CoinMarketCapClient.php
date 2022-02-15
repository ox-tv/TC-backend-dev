<?php


namespace App\Libraries;


use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoinMarketCapClient
{
    private $api_key;
    private $status;
    private $base_url = 'https://pro-api.coinmarketcap.com';

    public function __construct()
    {
        $this->api_key = config("coinmarketcap.api_key");
        $this->status = config("coinmarketcap.status");
    }


    public function GetPrices($ids): array
    {
        $result = [
            'http_code' => 0,
            'error_code' => 0,
            'error_message' => '',
            'data' => [],
        ];

        if (strtolower($this->status) != 'on'){
            $result['error_message'] = 'CoinMarketCap Client is turn off.';
            return $result;
        }

        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'X-CMC_PRO_API_KEY' => $this->api_key,
                ]
            ])->get("{$this->base_url}/v1/cryptocurrency/quotes/latest",[
                "id" => implode(',', $ids),
                "aux" => 'date_added',
            ]);

            $body = $response->json();

            if(!$response->successful()){
                $result['error_code'] = $body['status']['error_code'];
                throw new Exception($body['status']['error_message'], $response->status());
            }

            $result['http_code'] = $response->status();

            foreach ($body['data'] as $value){
                $result['data'][$value['id']] = $value['quote']['USD'];
            }

            return $result;

        }catch(Exception $e){
            $result['http_code'] = $e->getCode();
            $result['error_message'] = $e->getMessage();
            Log::channel('coinmarketcap')->error("CoinMarketCap GetPrices Api Error: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return $result;
    }


    public function GetInfo($ids)
    {
        $result = [
            'http_code' => 0,
            'error_code' => 0,
            'error_message' => '',
            'data' => [],
        ];

        if (strtolower($this->status) != 'on'){
            $result['error_message'] = 'CoinMarketCap Client is turn off.';
            return $result;
        }

        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'X-CMC_PRO_API_KEY' => $this->api_key,
                ]
            ])->get("{$this->base_url}/v1/cryptocurrency/info",[
                "id" => implode(',', $ids),
            ]);

            $body = $response->json();

            if(!$response->successful()){
                $result['error_code'] = $body['status']['error_code'];
                throw new Exception($body['status']['error_message'], $response->status());
            }

            $result['http_code'] = $response->status();

            foreach ($body['data'] as $value){
                $result['data'][$value['id']] = $value;
            }

        }catch(Exception $e){
            $result['http_code'] = $e->getCode();
            $result['error_message'] = $e->getMessage();
            Log::channel('coinmarketcap')->error("CoinMarketCap GetInfo Api Error: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return $result;
    }

    public function GetCryptoCurrencies($start = 1, $limit = 1000)
    {
        $result = [
            'http_code' => 0,
            'error_code' => 0,
            'error_message' => '',
            'data' => [],
        ];

        if (strtolower($this->status) != 'on'){
            $result['error_message'] = 'CoinMarketCap Client is turn off.';
            return $result;
        }

        try {
            $response = Http::withOptions([
                    'verify' => false,
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-CMC_PRO_API_KEY' => $this->api_key,
                    ]
                ])->get("{$this->base_url}/v1/cryptocurrency/listings/latest",[
                    "start" => $start,
                    "limit" => $limit,
                    "aux" => 'date_added,cmc_rank',
                ]);

            $body = $response->json();

            if(!$response->successful()){
                $result['error_code'] = $body['status']['error_code'];
                throw new Exception($body['status']['error_message'], $response->status());
            }

            $result['http_code'] = $response->status();
            $result['data'] = $body['data'];

            return $result;

        }catch(Exception $e){
            $result['http_code'] = $e->getCode();
            $result['error_message'] = $e->getMessage();
            Log::channel('coinmarketcap')->error("CoinMarketCap GetCryptoCurrencies Api Error: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return $result;
    }
}