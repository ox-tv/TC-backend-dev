<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegister;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{

    public function register(UserRegister $request)
    {
        $user = User::where('email', $request->get('email'))->whereNull('email_verified_at')->first();

        if(is_null($user)){
            $user = new User();
        }

        $verificationCode = rand(111111, 999999);

        if (env('APP_DEBUG', false) == true) {
            $verificationCode = 111111;
        }

        $user->email = $request->get('email');
        $user->password = Hash::make($request->get('password'));
        $user->verification_code = $verificationCode;
        $user->save();

        // TODO:: send verification code by email

        return response()->json([
            'email' => $request->input('email'),
            'message' => __('users.messages.verification_code_sent'),
        ]);

    }

}
