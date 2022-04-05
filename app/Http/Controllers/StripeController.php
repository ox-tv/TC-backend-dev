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

}
