<?php


namespace App\Libraries;


use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoinGeckoClient
{
    private $api_key;
    private $status;
    private $base_url = 'https://pro-api.coingecko.com/api/v3';

    public function __construct()
    {
        $this->api_key = config("coingecko.api_key");
        $this->status = config("coingecko.status");
    }


    public function GetCoinsList(): array
    {
        $result = [
            'success' => false,
            'error_message' => '',
            'data' => [],
        ];

        if (strtolower($this->status) != 'on'){
            $result['error_message'] = 'CoinGecko Client is turned off.';
            return $result;
        }

        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ])->get("{$this->base_url}/coins/list",[
                "x_cg_pro_api_key" => $this->api_key,
            ]);

            if(!$response->successful()){
                throw new Exception($response->body(), $response->status());
            }

            $result['success'] = true;
            $result['data'] = $response->json();

            return $result;

        }catch(Exception $e){
            $result['error_message'] = $e->getMessage();
            Log::channel('coingecko')->error("CoinGecko Get Coins List: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return $result;
    }

    public function GetCoinDetails($slug): array
    {
        $result = [
            'success' => false,
            'error_message' => '',
            'data' => [],
        ];

        if (strtolower($this->status) != 'on'){
            $result['error_message'] = 'CoinGecko Client is turned off.';
            return $result;
        }

        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ])->get("{$this->base_url}/coins/{$slug}",[
                "x_cg_pro_api_key" => $this->api_key,
                "tickers" => "false",
                "market_data" => "false",
                "community_data" => "false",
                "developer_data" => "false",
                "sparkline" => "false",
            ]);

            if(!$response->successful()){
                throw new Exception($response->body(), $response->status());
            }

            $result['success'] = true;
            $result['data'] = $response->json();

            return $result;

        }catch(Exception $e){
            $result['error_message'] = $e->getMessage();
            Log::channel('coingecko')->error("CoinGecko Get Coin details: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return $result;
    }


    public function GetMarketData($options = []): array
    {
        $vsCurrency = $options['vs_currency']?? 'usd';
        $slugs = $options['slugs']?? [];
        $order = $options['order']?? 'market_cap_desc';
        $per_page = $options['per_page']?? 250;
        $page = $options['page']?? 1;

        $result = [
            'success' => false,
            'error_message' => '',
            'data' => [],
        ];

        if (strtolower($this->status) != 'on'){
            $result['error_message'] = 'CoinGecko Client is turned off.';
            return $result;
        }

        try {
            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ])->get("{$this->base_url}/coins/markets",[
                "x_cg_pro_api_key" => $this->api_key,
                "vs_currency" => $vsCurrency,
                "ids" => implode(',', $slugs),
                "order" => $order,
                "per_page" => $per_page,
                "page" => $page,
                "price_change_percentage" => '1h,24h,7d,14d,30d,200d,1y',
            ]);

            if(!$response->successful()){
                throw new Exception($response->body(), $response->status());
            }

            $result['success'] = true;
            $result['data'] = $response->json();

            return $result;

        }catch(Exception $e){
            $result['error_message'] = $e->getMessage();
            Log::channel('coingecko')->error("CoinGecko Get Coins List: {$e->getMessage()}");
            // TODO: send mail to admin
        }

        return $result;
    }
}