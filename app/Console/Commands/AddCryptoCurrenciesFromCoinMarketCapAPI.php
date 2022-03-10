<?php

namespace App\Console\Commands;

use App\Libraries\CoinMarketCapClient;
use App\Models\CryptoCurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
        $limit = 200;
        $available_coins = [];

        do{
            $response = $client->GetCryptoCurrencies($start, $limit);

            if ($response['http_code'] == 429){
                sleep(60);
                continue;
            }

            if(empty($response['data'])){
                break;
            }

            $info = [];
            $infoResponse = $client->GetInfo(array_column($response['data'], 'id'));

            if ($infoResponse['http_code'] == 429){
                sleep(60);
                continue;
            }

            if(empty($infoResponse['data'])){
                break;
            }

            $info = $infoResponse['data'];

            $data = [];
            foreach ($response['data'] as $value){
                $data[] = [
                    'status' => CryptoCurrency::STATUS_LIST,
                    'symbol' => $value['symbol'],
                    'name' => $value['name'],
                    'slug' => $value['slug'],
                    'coinmarketcap_id' => $value['id'],
                    'prices' => json_encode($value['quote']['USD']),
                    'metadata' => !empty($info[$value['id']])? json_encode($info[$value['id']]) : null,
                    'order' => $value['cmc_rank'],
                ];

                $available_coins[] = $value['id'];
            }

            CryptoCurrency::upsert(
                $data,
                ['coinmarketcap_id'],
                ['name', 'symbol', 'slug', 'order', 'metadata', 'status', 'prices']
            );

            $start += $limit;
        } while(1);

        CryptoCurrency::whereNotIn('coinmarketcap_id', $available_coins)->update([
            'order' => 100000,
            'status' => CryptoCurrency::STATUS_DELIST,
        ]);

        return 0;
    }
}
