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

class CheckEmailVerification
{
    private $EmailVerificationService;

    public function __construct(EmailVerificationService $EmailVerificationService)
    {
        $this->EmailVerificationService = $EmailVerificationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
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
                        if ($value && !AuthKey::where('auth_key', $value)->exists()) {
                            $fail('The '.$attribute.' is invalid.');
                        }
//                        if ($value && !Cache::has($value)) {
//                            $fail('The '.$attribute.' is invalid.');
//                        }
                    },
                ],
            ]);

            $authKeyModel = AuthKey::where('auth_key', $request->get('auth-key'))->first();
            $user = $authKeyModel->user()->firstOrFail();
//            $userId = Cache::get($request->get('auth-key'));
//            $user = User::where('id', $userId)->firstOrFail();

        }else{
            return response()->json([
                "message" => "Unauthenticated."
            ], 401);
        }

        if (!$this->EmailVerificationService->check($user)){
            return response()->json([
                'message' => 'Please pass email verification',
                'code' => 'email_verification.require',
            ], 403);
        }

        return $next($request);
    }
}
