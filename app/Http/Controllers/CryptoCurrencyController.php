<?php

namespace App\Http\Controllers;

use App\Http\Resources\CryptoCurrency\CryptoCurrencyItem;
use App\Libraries\CoinMarketCapClient;
use App\Models\Category;
use App\Models\CryptoCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CryptoCurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = CryptoCurrency::query();

        $filters = $request->get('filters', []);

        $searchFilter = Arr::get($filters, 'search');

        if($searchFilter){
            $query->where(function ($query) use ($searchFilter){
                $query->where(function ($query) use ($searchFilter){
                    $query->SearchName($searchFilter);
                })->orWhere(function ($query) use ($searchFilter){
                    $query->SearchSymbol($searchFilter);
                });
            });
        }

        if ($request->get('per_page') == -1){
            $data = $query->get();
        }else{
            $data = $query->paginate();
        }

        return CryptoCurrencyItem::collection($data);
    }

    public function GetRatio(Request $request)
    {
        //dd(cache()->get("crypto_currency_1_USD"));
        $request->validate([
            'ids' => ['required', 'array']
        ]);

        $ids = $request->get("ids");

        $crypto_currencies = CryptoCurrency::whereIn('id', $ids)->get();

        $result = [];
        $need_to_get = [];

        foreach($crypto_currencies as $crypto_currency){
            if(cache()->has("crypto_currency_{$crypto_currency->id}_USD")){
                $result[] = cache()->get("crypto_currency_{$crypto_currency->id}_USD");
            }else{
                $need_to_get[] = $crypto_currency;
            }
        }

        if(!empty($need_to_get)){
            $client = new CoinMarketCapClient();

            $slugs = array_column($need_to_get, 'slug');

            $res = $client->GetPriceRatio(implode(',', $slugs))?? abort(404);

            foreach($need_to_get as $crypto_currency){
                $result[] = cache()->remember(
                    "crypto_currency_{$crypto_currency->id}_USD",
                    60 * 5,
                    function () use ($crypto_currency, $res) {
                        $crypto_currency->ratio = $res[$crypto_currency->slug]?? abort(404);
                        $resource = CryptoCurrencyItem::make($crypto_currency);
                        return $resource;
                });
            }
        }

        return $result;
    }

}
