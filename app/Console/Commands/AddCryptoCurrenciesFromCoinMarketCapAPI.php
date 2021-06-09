<?php

namespace App\Console\Commands;

use App\Libraries\CoinMarketCapClient;
use App\Models\CryptoCurrency;
use Illuminate\Console\Command;

class AddCryptoCurrenciesFromCoinMarketCapAPI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto_currencies:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add crypto currencies from CoinMarketCap API';

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
        $limit = 1000;
        $available_slugs = [];

        do{
            $response = $client->GetCryptoCurrencies($start, $limit);

            if(empty($response['data'])){
                break;
            }

            $data = [];
            foreach ($response['data'] as $order => $value){
                $data[] = [
                    'symbol' => $value['symbol'],
                    'name' => $value['name'],
                    'slug' => $value['slug'],
                    'coinmarketcap_id' => $value['id'],
                    'order' => $start + $order,
                ];

                $available_slugs[] = $value['slug'];
            }

            CryptoCurrency::upsert($data, ['slug'], ['name', 'symbol', 'coinmarketcap_id', 'order']);

            $start += $limit;

        } while(1);

        CryptoCurrency::whereNotIn('slug', $available_slugs)->update([
            'order' => 100000,
            'status' => CryptoCurrency::STATUS_DELIST,
        ]);

        return 0;
    }
}
