<?php

namespace App\Console\Commands;

use App\Libraries\CoinGeckoClient;
use App\Models\CryptoCurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCryptoCurrenciesPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto_currencies:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update crypto currencies prices';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client = new CoinGeckoClient();

        $page = 1;
        $perPage = 250;

        $response = $client->GetMarketData([
            'per_page' => $perPage,
            'page' => $page
        ]);

        if(!$response['success']){
            return 0;
        }

        $data = [];
        $listed_coins = [];
        foreach ($response['data'] as $value){
            $data[] = [
                'status' => CryptoCurrency::STATUS_LIST,
                'symbol' => $value['symbol'],
                'name' => $value['name'],
                'slug' => $value['id'],
                'order' => $value['market_cap_rank'],
                'prices' => json_encode([
                    "market_cap" => $value['market_cap'],
                    "total_volume" => $value['total_volume'],
                    "fully_diluted_valuation" => $value['fully_diluted_valuation'],
                    "last_updated" => $value['last_updated'],

                    "price"=> $value['current_price'],
                    "high_24h" => $value['high_24h'],
                    "low_24h" => $value['low_24h'],

                    "price_change_24h" => $value['price_change_24h'],
                    "percent_change_24h" => $value['price_change_percentage_24h_in_currency'],

                    "percent_change_1h" => $value['price_change_percentage_1h_in_currency'],
                    "percent_change_7d" => $value['price_change_percentage_7d_in_currency'],
                    "percent_change_14d" => $value['price_change_percentage_14d_in_currency'],
                    "percent_change_30d" => $value['price_change_percentage_30d_in_currency'],
                    "percent_change_200d" => $value['price_change_percentage_200d_in_currency'],
                    "percent_change_1y" => $value['price_change_percentage_1y_in_currency'],
                ]),
            ];

            $listed_coins[] = $value['id'];
        }

        CryptoCurrency::upsert($data, ['slug'], ['name', 'symbol', 'slug', 'order', 'prices', 'status']);

        // Update orders without touch updated_at
        DB::table('crypto_currencies')->whereNotIn('slug', $listed_coins)->update([
            'order' => 1000000,
        ]);

        return 0;
    }
}
