<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PointController extends Controller
{
    public function pointToUsdRate()
    {
        return config('general.points.to_usd_rate');
    }
}
