<?php

namespace App\Console\Commands;

use App\Libraries\CoinGeckoClient;
use App\Models\CryptoCurrency;
use App\Models\CryptoCurrencyPrice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCryptoCurrenciesPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto_currencies:update {--updateOnlyFirst250= : true/false}';

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

    private $listedCoins = [];
    private $updateOnlyFirst250 = true;

    public function handle()
    {
        $this->updateOnlyFirst250 = filter_var($this->option('updateOnlyFirst250'), FILTER_VALIDATE_BOOLEAN);

        if ($this->updateOnlyFirst250){

            $startTime = time();
            while(time() - $startTime < 59){
                if (!$this->updateCoins(1)){
                    break;
                }
                sleep(20);
            }

        }else{
            $page = 1;
            $maxPages = 20;
            while ($page <= $maxPages){
                if (!$this->updateCoins($page)){
                    break;
                }
                $page++;
            }

            // Update orders without touch updated_at
            DB::table('crypto_currencies')->whereNotIn('slug', $this->listedCoins)->update([
                'order' => 1000000,
            ]);
        }

        return 0;
    }

    private function updateCoins($page = 1, $perPage = 250)
    {
        $client = new CoinGeckoClient();

        $response = $client->GetMarketData([
            'per_page' => $perPage,
            'page' => $page
        ]);

        if(!$response['success']){
            return false;
        }

        $data = [];
        $priceData = [];
        $socketData = [];
        $hasUnRankCoin = false;
        foreach ($response['data'] as $value){

            if (!$value['market_cap_rank']){
                $hasUnRankCoin = true;
                break;
            }

            if (!cache()->has("CryptoCurrencyPrice_{$value['id']}_inserted")){
                cache()->put("CryptoCurrencyPrice_{$value['id']}_inserted", true, Carbon::now()->addMinutes(4)->addSeconds(55));

                $priceData[] = [
                    "slug" => $value['id'],
                    "price"=> $value['current_price'],
                    "last_updated" => CryptoCurrencyPrice::fromDateTime(Carbon::parse($value['last_updated'])),
                ];
            }

            $data[] = [
                'status' => CryptoCurrency::STATUS_LIST,
                'symbol' => $value['symbol'],
                'name' => $value['name'],
                'slug' => $value['id'],
                'order' => $value['market_cap_rank'],
                'prices' => json_encode($this->getPricesData($value)),
            ];

            if ($this->updateOnlyFirst250){
                $socketData[] = [
                    'symbol' => $value['symbol'],
                    'name' => $value['name'],
                    'slug' => $value['id'],
                    'order' => $value['market_cap_rank'],
                    'prices' => $this->getPricesData($value),
                ];
            }

            $this->listedCoins[] = $value['id'];
        }

        CryptoCurrency::upsert($data, ['slug'], ['name', 'symbol', 'slug', 'order', 'prices', 'status']);
        CryptoCurrencyPrice::insert($priceData);
        return !$hasUnRankCoin;
        if ($this->updateOnlyFirst250){
            broadcast(new \App\Events\Market($socketData));
        }

        return !$hasUnRankCoin;
    }

    private function getPricesData($value)
    {
        return [
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
        ];
    }
}
