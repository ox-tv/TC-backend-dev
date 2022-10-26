<?php

namespace App\Console\Commands;

use App\Libraries\CoinMarketCapClient;
use App\Models\CryptoCurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCryptoCurrenciesPricesFromCoinMarketCapAPI extends Command
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
    protected $description = 'update crypto currencies prices from CoinMarketCap API';

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
        $client = new CoinMarketCapClient();

        $start = 1;
        $limit = 300;
        $available_coins = [];

        $response = $client->GetCryptoCurrencies($start, $limit);

        if(empty($response['data'])){
            return;
        }

        $data = [];
        foreach ($response['data'] as $value){
            $data[] = [
                'status' => CryptoCurrency::STATUS_LIST,
                'symbol' => $value['symbol'],
                'name' => $value['name'],
                'slug' => $value['slug'],
                'coinmarketcap_id' => $value['id'],
                'prices' => json_encode($value['quote']['USD']),
                'order' => $value['cmc_rank'],
            ];

            $available_coins[] = $value['id'];
        }

        CryptoCurrency::upsert($data, ['coinmarketcap_id'], ['name', 'symbol', 'slug', 'order', 'prices', 'status']);

        // Update orders without touch updated_at
        DB::table('crypto_currencies')->whereNotIn('coinmarketcap_id', $available_coins)->update([
            'order' => 100000,
            //'status' => CryptoCurrency::STATUS_DELIST,
        ]);
        return 0;
    }
}
