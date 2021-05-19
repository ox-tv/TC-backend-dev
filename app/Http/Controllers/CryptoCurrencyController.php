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

        return CryptoCurrencyItem::collection($query->get());
    }

    public function GetRatio(Request $request)
    {
        $request->validate([
            'symbols' => ['required']
        ]);

        $symbols = explode(',', $request->get("symbols"));

        $result = [];
        $need_to_get = [];

        foreach($symbols as $symbol){
            if(cache()->has("crypto_currency_{$symbol}_USD")){
                $result[$symbol] = cache()->get("crypto_currency_{$symbol}_USD");
            }else{
                $need_to_get[] = $symbol;
            }
        }

        if(!empty($need_to_get)){
            $client = new CoinMarketCapClient();
            $res = $client->GetPriceRatio(implode(',', $need_to_get))?? abort(404);

            foreach($need_to_get as $symbol){
                $result[$symbol] = cache()->remember("crypto_currency_{$symbol}_USD", 60 * 5, function () use ($symbol, $res) {
                    return $res[$symbol]?? abort(404);
                });
            }
        }

        return $result;
    }

}
