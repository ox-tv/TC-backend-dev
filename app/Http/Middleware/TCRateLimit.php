<?php

namespace App\Http\Middleware;

use App\Models\SecurityRateLimit;
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

        $ip = getClientIP();
        $userID = $request->user()? $request->user()->id: null;
        $routeName = $request->route()->getActionName();
        $datetime = Carbon::now()->toDateTimeString();

        if (Cache::get("{$routeName}.ip{$ip}.block")){
            $this->saveToDB([
                'ip_address' => $ip,
                'user_id' => $userID,
                'route' => $routeName,
                'is_blocked' => true,
            ]);
            //Log::channel('ratelimit')->error("IP:{$ip} / date: {$datetime} / UserID: {$userID} / {$routeName}");
            abort(403, "Too many Requests...");
        }


        $count = intval(Cache::get("{$routeName}.ip{$ip}.count"));

        if ($count > $maxRequest){
            Cache::put("{$routeName}.ip{$ip}.block", true, $this->getCacheTTLFromNow($blockNumber, $blockUnit));
            //Log::channel('ratelimit')->error("IP:{$ip} / date: {$datetime} / UserID: {$userID} / {$routeName}");
            $this->saveToDB([
                'ip_address' => $ip,
                'user_id' => $userID,
                'route' => $routeName,
                'is_blocked' => true,
            ]);
            abort(403, "Too many Requests...");
        }

        $this->saveToDB([
            'ip_address' => $ip,
            'user_id' => $userID,
            'route' => $routeName,
            'is_blocked' => false,
        ]);

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

        $ip = getClientIP();
        $routeName = $request->route()->getActionName();

        $count = intval(Cache::get("{$routeName}.ip{$ip}.count"));
        $count = $count + 1;
        Cache::put("{$routeName}.ip{$ip}.count", $count, $this->getCacheTTLByPeriod($periodNumber, $periodUnit));
    }


    private function getCacheTTLByPeriod($periodNumber, $periodUnit)
    {
        switch ($periodUnit){
            case 's':{
                $ttlCache = Carbon::now()->setSecond(floor(Carbon::now()->second / $periodNumber + 1) * $periodNumber)->setMillisecond(0);
                break;
            }
            case 'm':{
                $ttlCache = Carbon::now()->setMinute(floor((Carbon::now()->minute) / $periodNumber + 1) * $periodNumber)->setSecond(0)->setMillisecond(0);
                break;
            }
            case 'h':{
                $ttlCache = Carbon::now()->setHour(floor(Carbon::now()->hour / $periodNumber + 1) * $periodNumber)->setMinute(0)->setSecond(0)->setMillisecond(0);
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

    private function saveToDB($data)
    {
        SecurityRateLimit::create([
            'ip_address' => $data['ip_address'],
            'user_id' => $data['user_id'],
            'route' => $data['route'],
            'is_blocked' => !empty($data['is_blocked']),
        ]);
    }
}
