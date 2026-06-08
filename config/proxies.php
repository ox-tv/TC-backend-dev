<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Configures App\Http\Middleware\TrustProxies. Set the TRUSTED_PROXIES env
    | var to the proxies in front of the app so Laravel honors the
    | X-Forwarded-* headers (e.g. correct https scheme behind nginx/Cloudflare).
    |
    | Accepts:
    |   "*"               trust all proxies (use when the app is only reachable
    |                     through your own reverse proxy / CDN)
    |   "ip1,ip2,..."     comma-separated list of trusted proxy IPs/CIDRs
    |   null / unset      trust none (default)
    |
    */

    'trusted' => env('TRUSTED_PROXIES'),

];
