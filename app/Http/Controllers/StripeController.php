<?php

namespace App\Http\Controllers;

use App\Http\Resources\CryptoCurrency\CryptoCurrencyItem;
use App\Libraries\CoinBaseClient;
use App\Libraries\CoinMarketCapClient;
use App\Models\Category;
use App\Models\CryptoCurrency;
use App\Models\Plan;
use App\Models\Pricing;
use App\Models\PricingUser;
use App\Models\Transaction;
use App\Models\User;
use App\Repository\Eloquent\PricingRepository;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function setupIntent()
    {
        $data = [
            'intent' => auth()->user()->createSetupIntent(),
            'payment_methods' => auth()->user()->paymentMethods(),
        ];

        return response()->json($data);
    }

}
