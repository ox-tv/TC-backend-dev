<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserVerified;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegister;
use App\Http\Requests\UserResendVerification;
use App\Mail\VerificationMail;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterController extends Controller
{

    public function register(UserRegister $request)
    {
        $duplicateEmail = User::where('email', $request->get('email'))->whereNotNull('email_verified_at')->exists();

        if ($duplicateEmail){
            return response()->json([
                'message' => __('auth.email_already_taken'),
            ],422);
        }

        $user = User::where('email', $request->get('email'))->whereNull('email_verified_at')->first();

        if(is_null($user)){
            $user = new User();
        }

        do{
            $referral_code = strtoupper(Str::random(6));
        }while(User::where('referral_code', $referral_code)->exists());

        $user->referral_code = $referral_code;
        $user->email = $request->get('email');
        $user->password = Hash::make($request->get('password'));

        if($request->get('referral_code')){
            $referrer = User::where('referral_code', $request->get('referral_code'))->first();
            $user->referrer_id = $referrer->id;
        }

        $user->save();

        // Create verification token and send to user email
        $token = sha1($user->id . time());
        $user->verification_code = $token;
        $user->save();

        $link = config('general.EMAIL_VERIFICATION_URL').$token;
        Mail::to($user->email)
            ->queue(new VerificationMail($link));

        return response()->json([
            'email' => $request->input('email'),
            'message' => __('users.messages.verification_link_sent'),
        ]);
    }

    public function verify($token)
    {
        $user = User::where("verification_code", $token)->firstOrFail();

        $user->email_verified_at = now();
        $user->status = User::STATUS_ACTIVE;

        $user->save();

        event(new UserVerified($user));

        return response()->json(['message' => __('users.messages.verified')], 200);
    }

    public function resend(UserResendVerification $request)
    {
        $user = User::where("email", $request->get("email"))->firstOrFail();

        $token = sha1($user->id . time());
        $user->verification_code = $token;
        $user->save();

        $link = config('general.EMAIL_VERIFICATION_URL').$token;
        Mail::to($user->email)
            ->queue(new VerificationMail($link));

        return response()->json([
            'email' => $request->get("email"),
            'message' => __('users.messages.verification_link_resent'),
        ]);
    }

}
