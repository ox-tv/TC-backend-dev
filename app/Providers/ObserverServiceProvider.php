<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Video;
use App\Observers\NotificationObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Notification::observe(NotificationObserver::class);
    }
}
