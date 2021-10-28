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

        $need_to_get_ratio = [];
        foreach($data as $crypto_currency){
            if(empty($crypto_currency->prices) || $crypto_currency->updated_at < Carbon::now()->subMinutes(10)){
                $need_to_get_ratio[] = $crypto_currency;
            }
        }
        if (!empty($need_to_get_ratio)){
            $this->GetRatios($need_to_get_ratio);
        }

        return CryptoCurrencyItem::collection($data);
    }

    private function GetRatios($crypto_currencies)
    {
        $client = new CoinMarketCapClient();

        $slugs = array_column($crypto_currencies, 'slug');

        $res = $client->GetPriceRatio(implode(',', $slugs))?? abort(404);

        foreach($crypto_currencies as $crypto_currency){
            $crypto_currency->prices = $res[$crypto_currency->slug]??  null;
            $crypto_currency->save();
        }

        return true;
    }

    public function favorites()
    {
        $data = auth('api')->user()->favoriteCryptoCurrencies()->get();

        $ratios = $this->GetRatios($data);

        foreach($data as $crypto_currency){
            $crypto_currency->ratio = $ratios[$crypto_currency->id];
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
