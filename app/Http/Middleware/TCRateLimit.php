<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
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
        if (count($params) != 5){
            abort(403, "Invalid request...");
        }


        $request->attributes->add(["params" => $params]);
        $maxRequest = $params[0]; // 100
        $periodNumber = $params[1]; // 10
        $periodUnit = $params[2]; // m
        $blockNumber = $params[3]; // 1
        $blockUnit = $params[4]; // h

        $ip = getClientIP($request);
        if (Cache::get("{$request->route()->getActionName()}.ip{$ip}.block")){
            abort(403, "Too many Requests...");
        }


        $count = intval(Cache::get("{$request->route()->getActionName()}.ip{$ip}.count"));

        if ($count > $maxRequest){
            Cache::put("{$request->route()->getActionName()}.ip{$ip}.block", true, $this->getCacheTTLFromNow($blockNumber, $blockUnit));
            abort(403, "Too many Requests...");
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (!$response->isOk()){
            return;
        }

        $params = $request->attributes->get('params');
        $maxRequest = $params[0]; // 100
        $periodNumber = $params[1]; // 10
        $periodUnit = $params[2]; // m
        $blockNumber = $params[3]; // 1
        $blockUnit = $params[4]; // h

        $ip = getClientIP($request);
        $count = intval(Cache::get("{$request->route()->getActionName()}.ip{$ip}.count"));
        $count = $count + 1;
        Cache::put("{$request->route()->getActionName()}.ip{$ip}.count", $count, $this->getCacheTTLByPeriod($periodNumber, $periodUnit));
    }


    private function getCacheTTLByPeriod($periodNumber, $periodUnit)
    {
        switch ($periodUnit){
            case 's':{
                $ttlCache = Carbon::now()->setSecond(ceil(Carbon::now()->second / $periodNumber) * $periodNumber)->setMillisecond(0);
                break;
            }
            case 'm':{
                $ttlCache = Carbon::now()->setMinute(ceil(Carbon::now()->minute / $periodNumber) * $periodNumber)->setSecond(0)->setMillisecond(0);
                break;
            }
            case 'h':{
                $ttlCache = Carbon::now()->setHour(ceil(Carbon::now()->hour / $periodNumber) * $periodNumber)->setMinute(0)->setSecond(0)->setMillisecond(0);
                break;
            }
            default:{
                abort(403, "Invalid Request...");
            }
        }

        return $ttlCache;
    }


    private function getCacheTTLFromNow($blockNumber, $blockUnit)
    {
        switch ($blockUnit){
            case 's':{
                $ttlCache = Carbon::now()->addSeconds($blockNumber);
                break;
            }
            case 'm':{
                $ttlCache = Carbon::now()->addMinutes($blockNumber);
                break;
            }
            case 'h':{
                $ttlCache = Carbon::now()->addHours($blockNumber);
                break;
            }
            case 'D':{
                $ttlCache = Carbon::now()->addDays($blockNumber);
                break;
            }
            default:{
                abort(403, "Invalid Request...");
            }
        }

        return $ttlCache;
    }
}
