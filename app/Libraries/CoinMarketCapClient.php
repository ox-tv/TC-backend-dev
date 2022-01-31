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

    /*
     * Can pass multi symbols separated by comma
     * */
    public function GetPrices($slugs): array
    {
        $result = [];

        if (strtolower($this->status) != 'on'){
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
                "slug" => $slugs,
                "aux" => 'date_added',
            ]);

            $body = $response->json();

            if(!$response->successful()){
                throw new Exception($body['status']['error_message'], $response->status());
            }

            if (!empty($body['data'])){
                foreach ($body['data'] as $id => $value){
                    $result[$value['slug']] = $value['quote']['USD'];
                }
            }

        }catch(Exception $e){
            Log::error("CoinMarketCap GetPriceRatio Api Error: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return $result;
    }

    /*
     * Can pass multi symbols separated by comma
     * */
    public function GetInfo($ids)
    {
        $result = [];

        if (strtolower($this->status) != 'on'){
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
                throw new Exception($body['status']['error_message'], $response->status());
            }

            if (!empty($body['data'])){
                foreach ($body['data'] as $id => $value){
                    $result[$id] = $value;
                }
            }

        }catch(Exception $e){
            Log::error("CoinMarketCap GetPriceRatio Api Error: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return $result;
    }

    public function GetCryptoCurrencies($start = 1, $limit = 1000)
    {
        $result = [];

        if (strtolower($this->status) != 'on'){
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
                    "aux" => 'date_added',
                ]);

            if($response->successful()){
                return $response->json();
            }

            $body = $response->json();

            if(!$response->successful()){
                throw new Exception($body['status']['error_message'], $response->status());
            }

        }catch(Exception $e){
            Log::error("CoinMarketCap GetCryptoCurrencies Api Error: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return [];
    }
}