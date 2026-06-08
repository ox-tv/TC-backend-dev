<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;

    /**
     * Resolve trusted proxies from config (env-driven via config/proxies.php),
     * so running behind nginx/Cloudflare is configured by TRUSTED_PROXIES in
     * .env rather than a hardcoded value.
     */
    public function __construct()
    {
        $proxies = config('proxies.trusted');

        if ($proxies === '*' || $proxies === '**') {
            $this->proxies = $proxies;
        } elseif (is_string($proxies) && $proxies !== '') {
            $this->proxies = array_map('trim', explode(',', $proxies));
        }
    }
}
