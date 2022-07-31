<?php

namespace App\Http\Controllers\Auth;

use Amir\Permission\Models\Role;
use App\Events\UserVerified;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegister;
use App\Http\Requests\UserResendVerification;
use App\Mail\PublisherVerificationMail;
use App\Mail\VerificationMail;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    private $EmailVerificationService;

    public function __construct(EmailVerificationService $EmailVerificationService)
    {
        $this->EmailVerificationService = $EmailVerificationService;
    }

    public function register(UserRegister $request)
    {
        $scope = $request->is('api/publisher/*')? 'publisher' : 'mwa';

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


        // Create verification token and send to user email
        //auth()->emailVerification($user, $scope);
        $authKey = $this->EmailVerificationService->sendCode($user);
        
        return response()->json([
            'auth_key' => $authKey,
            'code' => 'email_verification.require',
            'email' => $request->input('email'),
        ]);
    }

    public function verify(): User
    {
        //$user = User::where("verification_code", $token)->firstOrFail();
        $user = auth('api')->user();

        if ($user->email_verified_at){
            return response()->json(['message' => __('auth.email_verified_already')]);
        }

        $user->email_verified_at = now();
        $user->status = User::STATUS_ACTIVE;
        $user->save();

        event(new UserVerified($user));

        return response()->json(['message' => __('auth.email_verified_successfully')]);
    }

    public function resend(UserResendVerification $request, $scope = 'user')
    {
        $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;

        $user = User::where("email", $request->get("email"))
            ->where(function($q) use($publisherRoleId) {
                $q->whereNull('role_id')
                    ->orWhere('role_id', $publisherRoleId);
            })
            ->whereNull('email_verified_at')
            ->firstOrFail();

        auth()->emailVerification($user, $scope);

        return response()->json([
            'email' => $request->get("email"),
            'message' => __('auth.email_verification_link_resent'),
        ]);
    }

}
