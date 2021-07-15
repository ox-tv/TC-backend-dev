<?php

namespace App\Providers;

use App\Repository\Eloquent\MessageRepository;
use App\Repository\Eloquent\PricingRepository;
use App\Repository\MessageRepositoryInterface;
use App\Repository\PricingRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
        $this->app->bind(PricingRepositoryInterface::class, PricingRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
