<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TCRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$params)
    {
        //dump("before request");
        //return $next($request);

        $ip = getClientIP($request);
        dump($ip);
        return $next($request);
        if (count($params) != 5){
            abort(403, "Invalid request");
        }

        $maxRequest = $params[0]; // 100
        $periodNumber = $params[1]; // 10
        $periodUnit = $params[2]; // m
        $blockNumber = $params[3]; // 1
        $blockUnit = $params[4]; // h




    }

    public function terminate(Request $request, Response $response): void
    {
        $ip = getClientIP($request);
        Log::channel('coinmarketcap')->error($ip);
        //dump($ip);
    }
}
