<?php

namespace App\Http\Middleware;

use App\Models\AuthKey;
use App\Models\User;
use App\Services\_2FAService;
use App\Services\EmailVerificationService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Check2FAorEmailVerification
{
    private $EmailVerificationService;
    private $_2faService;

    public function __construct(EmailVerificationService $EmailVerificationService, _2FAService $_2faService)
    {
        $this->EmailVerificationService = $EmailVerificationService;
        $this->_2faService = $_2faService;
    }

    public function handle(Request $request, Closure $next)
    {
        if($request->header('tc-auth-key')) {
            $request->merge(['auth-key' => $request->header('tc-auth-key')]);
        }

        if (auth('api')->check()){

            $user = auth('api')->user();

        }else if($request->get('auth-key')) {

            $request->validate([
                'auth-key' => [
                    'sometimes',
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


        $_2fa = $user->_2fa;

        if ($_2fa && ($_2fa->app_status || $_2fa->email_status)){
            // 2FA verification
            $errors = [];
            $_2faResult = $this->_2faService->check2FA($user, ['ip' => $request->ip()]);

            if (($_2fa->app_status && !$_2faResult['app']) || ($_2fa->email_status && !$_2faResult['email'])){
                $errors['app'] = $_2fa->app_status? 'Please verify app 2FA' : null;
                $errors['email'] = $_2fa->email_status? 'Please verify email 2FA' : null;
            }

            if (!empty($errors)){
                return response()->json([
                    'message' => 'Please verify 2FA',
                    'code' => '2fa.require',
                    'errors' => $errors
                ], 403);
            }

        }else if (!$this->EmailVerificationService->check($user)){
            // Email Verification
            $this->EmailVerificationService->sendCode($user);
            return response()->json([
                'message' => 'Please pass email verification',
                'code' => 'email_verification.require',
            ], 403);
        }

        return $next($request);
    }
}
