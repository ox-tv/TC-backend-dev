<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class StripeController extends Controller
{
    public function setupIntent()
    {
        $user = auth('api')->user();

        $data = [
            'intent' => $user->createSetupIntent(),
            'payment_methods' => $user->paymentMethods(),
        ];

        return response()->json($data);
    }

    public function cancelSubscription()
    {
        $user = auth('api')->user();

        if ($user->subscribed('default')) {

            //$user->subscription('default')->swap('price_1Kb4MoFmwVriBzKiuBL3QW0N');

            $user->subscription('default')->cancel();
        }

        return response()->json(['status' => 'ok']);
    }
}
