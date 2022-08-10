<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Notification;
use App\Observers\NotificationObserver;
use App\Observers\CommentObserver;
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
        Comment::observe(CommentObserver::class);
    }
}
