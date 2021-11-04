<?php

namespace App\Providers;

use App\Events\ChannelSubscribed;
use App\Events\CommentLiked;
use App\Events\UserVerified;
use App\Events\VideoCommented;
use App\Events\VideoUploaded;
use App\Events\VideoWatched;
use App\Listeners\ChannelStatisticsDailySubscribed;
use App\Listeners\ChannelStatisticsDailyVideoUploaded;
use App\Listeners\CommentLikedDataForUserStatisticsDaily;
use App\Listeners\StripeWebhookHandledListener;
use App\Listeners\UserVerifiedDataForUserStatisticsDaily;
use App\Listeners\VideoLikedDataForUserStatisticsDaily;
use App\Listeners\VideoStatisticsDailyCommented;
use App\Listeners\VideoViewedDataForUserStatisticsDaily;
use App\Listeners\VideoWatchedDataForUserStatisticsDaily;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\VideoViewed;
use App\Listeners\VideoStatisticsDailyIncreaseView;
use App\Events\VideoLiked;
use App\Listeners\VideoStatisticsDailyLiked;
use Laravel\Cashier\Events\WebhookReceived;

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
        UserVerified::class => [
            UserVerifiedDataForUserStatisticsDaily::class,
        ],
        VideoViewed::class => [
            VideoStatisticsDailyIncreaseView::class,
            VideoViewedDataForUserStatisticsDaily::class,
        ],
        VideoWatched::class => [
            VideoWatchedDataForUserStatisticsDaily::class,
        ],
        VideoLiked::class => [
            VideoStatisticsDailyLiked::class,
            VideoLikedDataForUserStatisticsDaily::class,
        ],
        CommentLiked::class => [
            CommentLikedDataForUserStatisticsDaily::class,
        ],
        ChannelSubscribed::class => [
            ChannelStatisticsDailySubscribed::class,
        ],
        VideoUploaded::class => [
            ChannelStatisticsDailyVideoUploaded::class,
        ],
        VideoCommented::class => [
            VideoStatisticsDailyCommented::class,
        ],
        WebhookReceived::class => [
            StripeWebhookHandledListener::class,
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
