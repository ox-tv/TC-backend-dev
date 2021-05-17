<?php


namespace App\Libraries;


use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoinMarketCapClient
{
    private $api_key;
    private $base_url = 'https://pro-api.coinmarketcap.com';

    public function __construct()
    {
        $this->api_key = '006c9e55-9015-4f2a-8186-7b20d4314f9f';//config("general.COIN_MARKET_CAP_API_KEY");
    }

    public function GetCryptoCurrencies($start = 1, $limit = 1000)
    {
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

            throw new Exception($response->status());

        }catch(Exception $e){
            Log::error("CoinMarketCap Api Error: {$e->getMessage()}");
        }

        return [];
    }
}