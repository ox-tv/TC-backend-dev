<?php

namespace App\Http\Middleware;

use App\Models\WAFNotValidRequestLog;
use App\Services\_2FAService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class CheckRequestHasValidHash
{

    public function handle(Request $request, Closure $next)
    {
        $secretKey = config('general.front-secret-key');
        if (empty($secretKey)){
            return $next($request);
        }

        $receivedHash = $request->header('X-TC-HASH');
        $dataToHash = json_encode($request->all());

        $expectedHash = hash('sha256', $dataToHash . $secretKey);

        if ($receivedHash !== $expectedHash) {
            $ip = getClientIP();
            $userID = $request->user()? $request->user()->id: null;
            $routeName = $request->route()->getActionName();
            WAFNotValidRequestLog::create([
                'ip_address' => $ip,
                'user_id' => $userID,
                'route' => $routeName,
            ]);

            //abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
