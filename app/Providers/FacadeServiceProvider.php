<?php

namespace App\Providers;

use App\Services\TCAuthManager;
use Illuminate\Support\ServiceProvider;

class FacadeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('auth', function ($app) {
            return new TCAuthManager($app);
        });
    }
}
