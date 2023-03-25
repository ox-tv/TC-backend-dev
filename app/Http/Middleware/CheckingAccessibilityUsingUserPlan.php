<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckingAccessibilityUsingUserPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$accessTypes Required to work, Can be unknown,free,publisher,hero
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$accessTypes)
    {
        $accessTypes = empty($accessTypes) ? [] : $accessTypes;

        if (in_array('unknown', $accessTypes)){
            return $next($request);
        }

        if (in_array('free', $accessTypes) && auth('api')->check()){
            return $next($request);
        }

        if (
            in_array('publisher', $accessTypes)
            && auth('api')->check()
            && auth('api')->user()->is_publisher
        ){
            return $next($request);
        }

        if (
            in_array('hero', $accessTypes)
            && auth('api')->check()
            && auth('api')->user()->is_hero
        ){
            return $next($request);
        }

        return response()->json([
            'message' => 'Access denied.',
            'code' => 'access.type.denied',
        ], 403);
    }
}
