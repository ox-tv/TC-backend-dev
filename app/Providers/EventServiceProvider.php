<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\VideoViewed;
use App\Listeners\VideoStatisticsDailyIncreaseView;
use App\Events\VideoLiked;
use App\Listeners\VideoStatisticsDailyLiked;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        VideoViewed::class => [
            VideoStatisticsDailyIncreaseView::class,
        ],
        VideoLiked::class => [
            VideoStatisticsDailyLiked::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
