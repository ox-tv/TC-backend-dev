<?php

namespace App\Http\Controllers;

use App\Http\Resources\CryptoCurrency\CryptoCurrencyItem;
use App\Libraries\CoinMarketCapClient;
use App\Models\Category;
use App\Models\CryptoCurrency;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CryptoCurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = CryptoCurrency::query();

        $filters = $request->get('filters', []);

        $searchFilter = Arr::get($filters, 'search');
        $idsFilter = Arr::get($filters, 'ids');

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

        if ($request->get('per_page') == -1){
            $data = $query->get();
        }else{
            $data = $query->paginate();
        }

        // check need prices
        $include = explode(',', $request->get('include', ''));
        $withPrices = in_array('prices', $include);

        if ($withPrices){
            $needToGetPrices = [];
            foreach($data as $crypto_currency){
                if(empty($crypto_currency->prices) || $crypto_currency->updated_at < Carbon::now()->subMinutes(10)){
                    $needToGetPrices[] = $crypto_currency;
                }
            }
            if (!empty($needToGetPrices)){
                $this->GetPrices($needToGetPrices);
            }
        }

        return CryptoCurrencyItem::collection($data);
    }

    private function GetPrices($crypto_currencies): void
    {
        $client = new CoinMarketCapClient();

        $slugs = array_column($crypto_currencies, 'slug');

        $res = $client->GetPrices(implode(',', $slugs));

        foreach($crypto_currencies as $crypto_currency){
            $crypto_currency->prices = $res[$crypto_currency->slug]??  null;
            $crypto_currency->save();
        }
    }

    public function favorites(Request $request)
    {
        $data = auth('api')->user()->favoriteCryptoCurrencies()->get();

        $include = explode(',', $request->get('include', ''));
        $withPrices = in_array('prices', $include);

        if ($withPrices){
            $needToGetPrices = [];
            foreach($data as $crypto_currency){
                if(empty($crypto_currency->prices) || $crypto_currency->updated_at < Carbon::now()->subMinutes(10)){
                    $needToGetPrices[] = $crypto_currency;
                }
            }
            if (!empty($needToGetPrices)){
                $this->GetPrices($needToGetPrices);
            }
        }

        foreach($data as $crypto_currency){
            $crypto_currency->is_favorite = true;
        }

        return CryptoCurrencyItem::collection($data);
    }

    public function addToFavorites($crypto_currency_id)
    {
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
}
