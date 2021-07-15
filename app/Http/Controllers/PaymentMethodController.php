<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentMethod\PaymentMethodItem;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return PaymentMethodItem::collection(PaymentMethod::all());
    }
}
