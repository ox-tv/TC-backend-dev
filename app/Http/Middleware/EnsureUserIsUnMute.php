<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsUnMute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $guard = auth('api')->check() ? 'api' : '';

        $user = auth($guard)->user();

        if (!$user){
            return $next($request);
        }

        if (!$user->is_mute){
            return $next($request);
        }

        abort(403, 'You are muted by administrators, Please contact supports.');
    }
}
