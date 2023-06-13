<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getMembershipData()
    {
        $user = auth('api')->user();

        return response()->json([
            'has_stripe_subscription' => $user->subscribed('default'),
            'is_hero' => $user->is_hero,
            'due_at' => $user->hero_due_at,
        ]);
    }
}
