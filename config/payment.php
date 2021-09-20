<?php

return [
    'coinbase' => [
        'api_key' => env('COINBASE_API_KEY'),
        'webhook_secret' => env('COINBASE_WEBHOOK_SECRET'),
        'base_url' => env('COINBASE_BASE_URL', 'https://api.commerce.coinbase.com'),
        'api_version' => env('COINBASE_API_VERSION', '2018-03-22'),
        'redirect_url' => env('COINBASE_REDIRECT_URL', ''),
        'cancel_url' => env('COINBASE_CANCEL_URL', ''),
    ],



];
