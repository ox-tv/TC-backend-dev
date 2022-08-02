<?php

namespace App\Http\Controllers\Auth;

use Amir\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegister;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    private $EmailVerificationService;

    public function __construct(EmailVerificationService $EmailVerificationService)
    {
        $this->EmailVerificationService = $EmailVerificationService;
    }

    public function register(UserRegister $request): \Illuminate\Http\JsonResponse
    {
        $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;

        $duplicateEmail = User::where('email', $request->get('email'))
            ->where(function($q) use($publisherRoleId) {
                $q->whereNull('role_id')
                    ->orWhere('role_id', $publisherRoleId);
            })->whereNotNull('email_verified_at')
            ->exists();

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

        $this->EmailVerificationService->sendCode($user);
        $authKey = sha1('email_verification.require.' . $user->id);
        Cache::put($authKey, $user->id, 24 * 60 * 60);
        
        return response()->json([
            'auth_key' => $authKey,
            'code' => 'email_verification.require',
            'email' => $request->input('email'),
        ]);
    }

}
