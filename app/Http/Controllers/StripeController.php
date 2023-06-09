<?php

namespace App\Http\Controllers;

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
