<?php

namespace App\Http\Middleware;

use App\Services\_2FAService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class Check2FA
{
    private $_2faService;

    public function __construct(_2FAService $_2faService)
    {
        $this->_2faService = $_2faService;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $level = 'soft')
    {
        if (!auth('api')->check()){
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = auth('api')->user();

        $_2fa = $user->_2fa;

        if (!$_2fa) {

            if ($level == 'hard'){
                return response()->json([
                    'message' => 'Please Enable 2FA',
                    'code' => '2fa.enable.require',
                ], 403);
            }

            return $next($request);
        }

        $errors = [];
        $_2faResult = $this->_2faService->check2FA($user, ['ip' => $request->ip()]);

        /*if (($_2fa->app_status && !$_2faResult['app']) || ($_2fa->email_status && !$_2faResult['email'])){
            $errors['app'] = 'Please verify app 2FA';
            $errors['email'] = 'Please verify email 2FA';
        }*/

        if (!$_2faResult['app'] || !$_2faResult['email']){
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

        return $next($request);
    }
}
