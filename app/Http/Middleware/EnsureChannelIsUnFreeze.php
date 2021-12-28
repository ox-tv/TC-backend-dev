<?php

namespace App\Http\Middleware;

use App\Models\Channel;
use Closure;
use Illuminate\Http\Request;

class EnsureChannelIsUnFreeze
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
        $channel = $user->channel;

        if (!$user || !$channel){
            return $next($request);
        }

        if ($channel->status != Channel::STATUS_FREEZE){
            return $next($request);
        }

        abort(403, __('channel.channel_freeze_info'));
    }
}
