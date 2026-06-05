<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\H ttp\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $user = auth('api')->user();

        if ($user && $user->status == User::STATUS_INACTIVE){

            $user->tokens->each(function($token, $key) {
                $token->delete();
            });

            return $next($request);
        }

        if (($user = auth('api')->user()) && !Cache::has('user_'.$user->id.'_last_actived_at_stored')){
            $user->last_actived_at = Carbon::now();
            $user->last_active_from_ip = getClientIP();
            $user->save();
            Cache::put('user_'.$user->id.'_last_actived_at_stored', true, 600);
        }

        return $next($request);
    }
}
