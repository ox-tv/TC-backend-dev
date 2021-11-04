<?php

namespace App\Console\Commands;

use App\Libraries\CoinMarketCapClient;
use App\Models\CryptoCurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $available_coins = [];

        do{
            $response = $client->GetCryptoCurrencies($start, $limit);

            if(empty($response['data'])){
                break;
            }

            $info = [];

            foreach (array_chunk(array_column($response['data'], 'slug'), 100) as $slugs){
                $infoResponse = $client->GetInfo(implode(',', $slugs));

                if(empty($infoResponse)){
                    continue;
                }

                $info = array_merge($info, $infoResponse);
            }

            $data = [];
            foreach ($response['data'] as $order => $value){
                $data[] = [
                    'status' => CryptoCurrency::STATUS_LIST,
                    'symbol' => $value['symbol'],
                    'name' => $value['name'],
                    'slug' => $value['slug'],
                    'coinmarketcap_id' => $value['id'],
                    'metadata' => json_encode($info[$value['slug']]?? null),
                    'order' => $start + $order,
                ];

                $available_coins[] = $value['id'];
            }

            DB::table('crypto_currencies')->upsert(
                $data,
                ['coinmarketcap_id'],
                ['name', 'symbol', 'slug', 'order', 'metadata', 'status']
            );

            $start += $limit;
        } while(1);

        DB::table('crypto_currencies')->whereNotIn('coinmarketcap_id', $available_coins)->update([
            'order' => 100000,
            'status' => CryptoCurrency::STATUS_DELIST,
        ]);

        return 0;
    }
}
