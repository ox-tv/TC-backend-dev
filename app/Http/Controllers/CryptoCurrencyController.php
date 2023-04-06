<?php

namespace App\Http\Controllers;

use App\Http\Resources\CryptoCurrency\CryptoCurrencyResource;
use App\Libraries\CoinGeckoClient;
use App\Libraries\CoinMarketCapClient;
use App\Models\CryptoCampaign;
use App\Models\CryptoCurrency;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CryptoCurrencyController extends Controller
{
    public function index(Request $request)
    {
        $isRelatedToCryptoCampaigns = $request->is('api/admin/cryptocurrencies/relatedto-campaigns');
        $isMarket = $request->is('api/market/cryptocurrencies');

        $query = CryptoCurrency::where('status', CryptoCurrency::STATUS_LIST);

        $filters = $request->get('filters', []);

        $searchFilter = Arr::get($filters, 'search');
        $isFavoriteFilter = Arr::get($filters, 'is_favorite');
        $idsFilter = Arr::get($filters, 'ids');
        $excludeStableCoinsFilter = (bool) Arr::get($filters, 'exclude_stable_coins');

        if($searchFilter){
            $query->where(function ($query) use ($searchFilter){
                $query->where(function ($query) use ($searchFilter){
                    $query->SearchName($searchFilter);
                })->orWhere(function ($query) use ($searchFilter){
                    $query->SearchSymbol($searchFilter);
                });
            });
        }

        if($idsFilter && is_array($idsFilter)){
            $query->whereIn('id', $idsFilter);
        }

        if($excludeStableCoinsFilter){
            $query->whereNotIn('symbol', config('general.stable_coins_symbol'));
        }

        if($isFavoriteFilter && auth('api')->check()){
            $query->whereHas('users', function ($q){
                $q->where('id', auth('api')->id());
            });
        }

        if ($isRelatedToCryptoCampaigns){
            $query->whereHas('cryptoCampaigns', function ($q){
                $q->where('status', CryptoCampaign::STATUS_ACTIVE);
            });
        }

        $sort = $request->get('sort');
        $sortDirection = $request->get('sort_direction')?? 'ASC';

        if($sort === 'market_cap'){
            $query->whereNotNull('prices')->orderByRaw("cast(prices->'$.market_cap' as float) {$sortDirection}");
        }elseif($sort === 'price'){
            $query->whereNotNull('prices')->orderByRaw("cast(prices->'$.price' as float) {$sortDirection}");
        }elseif($sort === '24h_percent'){
            $query->whereNotNull('prices')->orderByRaw("cast(prices->'$.percent_change_24h' as float) {$sortDirection}");
        }elseif($sort === '7d_percent'){
            $query->whereNotNull('prices')->orderByRaw("cast(prices->'$.percent_change_7d' as float) {$sortDirection}");
        }elseif($sort === '30d_percent'){
            $query->whereNotNull('prices')->orderByRaw("cast(prices->'$.percent_change_30d' as float) {$sortDirection}");
        }elseif($sort === 'top_24h'){
            $query->whereNotNull('prices')->where('order', '<=', 500)->orderByRaw("cast(prices->'$.percent_change_24h' as float) {$sortDirection}");
        }

        if ($request->get('per_page') == -1){
            $data = $query->get();
        }else{
            $data = $query->paginate($request->get('per_page', 50));
        }

        $data->append(['is_favorite']);

        if ($isMarket || $isRelatedToCryptoCampaigns){
            $data->load('activeCryptoCampaigns');
        }

        // Fill MetaData if empty
        $this->FillMetaDataColumn($data);
        /*
        // Update prices
        if ($request->is('api/market/cryptocurrencies')){
            $this->keepPricesUpdated($data);
        }*/

        return CryptoCurrencyResource::collection($data);
    }

    public function favorites(Request $request)
    {
        $cryptoCurrencies = auth('api')->user()
            ->favoriteCryptoCurrencies()
            ->where('status', CryptoCurrency::STATUS_LIST)
            ->get()
        ->append(['is_favorite']);

        $this->FillMetaDataColumn($cryptoCurrencies);
        /*$this->keepPricesUpdated($cryptoCurrencies);*/

        // Set is_favorite True on the fly without run any DB query
        $cryptoCurrencies->each(function ($crypto_currency, $key){
            $crypto_currency->is_favorite = true;
        });

        return CryptoCurrencyResource::collection($cryptoCurrencies);
    }

    public function addToFavorites($crypto_currency_id)
    {
        $user = auth('api')->user();

        if($user->has_free_plan && $user->favoriteCryptoCurrenciesCount() >= 5){
            return response()->json([
                'code' => 'crypto_currencies.fav.max_exceeded'
            ], 403);
        }

        $exists = CryptoCurrency::where([
            'id' => $crypto_currency_id,
            'status' => CryptoCurrency::STATUS_LIST
        ])->exists();

        abort_unless($exists, 404, 'Not Found');

        auth('api')->user()->favoriteCryptoCurrencies()->syncWithoutDetaching($crypto_currency_id);

        return response()->json(['message' => 'ok']);
    }

    public function removeFromFavorites($crypto_currency_id)
    {
        $exists = CryptoCurrency::where([
            'id' => $crypto_currency_id,
            'status' => CryptoCurrency::STATUS_LIST
        ])->exists();

        abort_unless($exists, 404, 'Not Found');

        auth('api')->user()->favoriteCryptoCurrencies()->detach($crypto_currency_id);

        return response()->json(['message' => 'ok']);
    }

    private function updatePrices($crypto_currencies): void
    {
        $client = new CoinMarketCapClient();
        $ids = array_column($crypto_currencies, 'coinmarketcap_id');

        $resPrices = $client->GetPrices($ids);

        if (empty($resPrices['data'])){
            return;
        }

        foreach($crypto_currencies as $crypto_currency){
            $crypto_currency->prices = $resPrices['data'][$crypto_currency->coinmarketcap_id]??  null;
            $crypto_currency->save();
        }
    }

    private function keepPricesUpdated($cryptoCurrencies): bool
    {
        $client = new CoinGeckoClient();
        $slugs = [];

        foreach($cryptoCurrencies as $cryptoCurrency){
            if(empty($cryptoCurrency->prices) || $cryptoCurrency->updated_at < Carbon::now()->subMinutes(5)){
                $slugs[] = $cryptoCurrency->slug;
            }
        }

        if (empty($slugs)){
            return false;
        }

        $response = $client->GetMarketData(['slugs' => $slugs]);

        if (!$response['success']){
            return false;
        }

        foreach ($response['data'] as $value){

            $cryptoCurrency = $cryptoCurrencies->where('slug', $value['id'])->first();

            $cryptoCurrency->prices = [
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

            $cryptoCurrency->save();
        }

        return true;
    }

    private function FillMetaDataColumn($cryptoCurrencies): bool
    {
        $client = new CoinGeckoClient();

        foreach($cryptoCurrencies as $crypto_currency){
            if(empty($crypto_currency->metadata)){
                $response = $client->GetCoinDetails($crypto_currency->slug);

                if (!$response['success']){
                    continue;
                }

                $crypto_currency->metadata = [
                    'image' => $response['data']['image'],
                    'links' => $response['data']['links'],
                    'description' => $response['data']['description']['en'],
                ];
                $crypto_currency->save();
            }
        }

        return true;
    }
}
