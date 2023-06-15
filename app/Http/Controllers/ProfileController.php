<?php

namespace App\Http\Controllers;


use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /*
     *
     * Get Data
     *
     */
    public function getMembershipData()
    {
        $user = auth('api')->user();

        return response()->json([
            'has_stripe_subscription' => $user->subscribed('default'),
            'is_hero' => $user->is_hero,
            'due_at' => $user->hero_due_at,
        ]);
    }

    public function getSecurityData()
    {
        $user = auth('api')->user();

        return response()->json([
            'login_type' => ($meta = $user->meta()->where('key', UserMeta::LoginTypes)->first())? $meta->value : [],
        ]);
    }

    /*
     *
     * Set Data
     *
     */
    public function setLoginType(Request $request)
    {
        $request->validate([
            'login_type' => ['required', 'array', Rule::in(['credentials', 'wallet'])],
        ]);

        $user = auth('api')->user();

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::LoginTypes],
            ['value' => json_encode($request->get('login_type'))]
        );

        return response()->json(['message' => 'ok']);
    }
}
