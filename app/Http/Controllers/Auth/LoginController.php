<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class LoginController extends Controller
{

    public function login(LoginRequest $request)
    {
        $attempt = Auth::attempt([
            'email' => $request->get('email'),
            'password' => $request->get('password'),
            'status' => User::STATUS_ACTIVE
        ]);

        if($attempt){

            $user = Auth::user();

            $result['profile'] = $user;
            $result['token'] =  $user->createToken('access_token')->accessToken;
            return response()->json($result, '200');

        }
        else{
            return response()->json(['code'=>401, 'message'=>__('auth.unauthorized')], 401);
        }
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse [string] message
     */
    public function logout(Request $request)
    {
        if($request->user()->token()){
            $request->user()->token()->revoke();
        }

        return response()->json([
            'message' => __('users.messages.success_logout_message')
        ]);

    }
}
