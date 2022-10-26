<?php

namespace App\Console\Commands;

use App\Libraries\CoinGeckoClient;
use App\Models\CryptoCurrency;
use Illuminate\Console\Command;

class AddCryptoCurrenciesFromCoinGeckoAPI extends Command
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
    protected $description = 'add crypto currencies from CoinGecko API';

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

        $response = $client->GetCoinsList();

        if (!$response['success']){
            return false;
        }

        if(empty($response['data'])){
            return false;
        }

        $listed_coins = [];
        foreach (array_chunk($response['data'], 300) as $chunk){
            $data = [];
            foreach ($chunk as $row){
                $data[] = [
                    'status' => CryptoCurrency::STATUS_LIST,
                    'symbol' => $row['symbol'],
                    'name' => $row['name'],
                    'slug' => $row['id'],
                ];

                $listed_coins[] = $row['id'];
            }

            CryptoCurrency::upsert(
                $data,
                ['slug'],
                ['name', 'symbol', 'slug', 'status']
            );
        }

        CryptoCurrency::whereNotIn('slug', $listed_coins)->update([
            'order' => 1000000,
            'status' => CryptoCurrency::STATUS_DELIST,
        ]);

        return 0;
    }
}
