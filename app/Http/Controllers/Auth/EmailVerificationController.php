<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserVerified;
use App\Http\Controllers\Controller;
use App\Models\AuthKey;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmailVerificationController extends Controller
{
    private $EmailVerificationService;

    public function __construct(EmailVerificationService $EmailVerificationService)
    {
        $this->EmailVerificationService = $EmailVerificationService;
    }

    public function sendCode(Request $request)
    {
        if($request->header('tc-auth-key')) {
            $request->merge(['auth-key' => $request->header('tc-auth-key')]);
        }

        // Detect user
        if (auth('api')->check()){

            $user = auth('api')->user();

        }else if($request->get('auth-key')) {
            $request->validate([
                'auth-key' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value && !AuthKey::where('auth_key')->exists()) {
                            $fail('The '.$attribute.' is invalid.');
                        }
//                        if ($value && !Cache::has($value)) {
//                            $fail('The '.$attribute.' is invalid.');
//                        }
                    },
                ],
            ]);

            $authKeyModel = AuthKey::where('auth_key')->first();
            $user = $authKeyModel->user()->firstOrFail();
//            $userId = Cache::get($request->get('auth-key'));
//            $user = User::where('id', $userId)->firstOrFail();

        }else{
            return response()->json([
                "message" => "Unauthenticated."
            ], 401);
        }

        $this->EmailVerificationService->sendCode($user);

        $authKeyModel = new AuthKey();
        $authKeyModel->auth_key = sha1('email_verification.require.' . $user->id);
        $authKeyModel->user_id = $user->id;
        $authKeyModel->save();
//        $authKey = sha1('email_verification.require.' . $user->id);
//        Cache::put($authKey, $user->id, 24 * 60 * 60);

        return response()->json([
            'auth_key' => $authKeyModel->auth_key,
            'status' => 'ok',
        ]);
    }

    public function verify(Request $request)
    {
        if($request->header('tc-auth-key')) {
            $request->merge(['auth-key' => $request->header('tc-auth-key')]);
        }

        // Detect user
        if (auth('api')->check()){

            $user = auth('api')->user();

        }else if($request->get('auth-key')) {

            $request->validate([
                'auth-key' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value && !AuthKey::where('auth_key')->exists()) {
                            $fail('The '.$attribute.' is invalid.');
                        }
//                        if ($value && !Cache::has($value)) {
//                            $fail('The '.$attribute.' is invalid.');
//                        }
                    },
                ],
            ]);

            $authKeyModel = AuthKey::where('auth_key')->first();
            $user = $authKeyModel->user()->firstOrFail();
//            $userId = Cache::get($request->get('auth-key'));
//            $user = User::where('id', $userId)->firstOrFail();

        }else{
            return response()->json([
                "message" => "Unauthenticated."
            ], 401);
        }

        // Checking Code
        $request->validate([
            'email_verification_code' => [
                'required', 'numeric', 'digits:6', function ($attribute, $value, $fail) use($user) {
                    if (!$this->EmailVerificationService->verify($user, $value)) {
                        $fail('Invalid/ expired code.');
                    }
                },
            ],
        ]);

        if (!$user->email_verified_at){
            $user->email_verified_at = now();
            $user->status = User::STATUS_ACTIVE;
            $user->save();

            event(new UserVerified($user));
        }

        return response()->json(['status' => 'ok']);
    }

}
