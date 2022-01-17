<?php

namespace App\Providers;

use App\Events\ChannelSubscribed;
use App\Events\CommentLiked;
use App\Events\UserVerified;
use App\Events\VideoCommented;
use App\Events\VideoCreated;
use App\Events\VideoDeleted;
use App\Events\VideoUpdated;
use App\Events\VideoWatched;
use App\Listeners\ChannelStatisticsDailySubscribed;
use App\Listeners\ChannelStatisticsDailyVideoCreated;
use App\Listeners\CommentLikedDataForUserStatisticsDaily;
use App\Listeners\SendNotificationOnVideoCreated;
use App\Listeners\SendNotificationOnVideoDeleted;
use App\Listeners\SendNotificationOnVideoUpdated;
use App\Listeners\StripeWebhookHandledListener;
use App\Listeners\UserVerifiedDataForUserStatisticsDaily;
use App\Listeners\VideoLikedDataForUserStatisticsDaily;
use App\Listeners\VideoStatisticsDailyCommented;
use App\Listeners\VideoViewedDataForUserStatisticsDaily;
use App\Listeners\VideoWatchedDataForUserStatisticsDaily;
use App\Listeners\VideoWatchedDataForVideoStatisticsDaily;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
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
            VideoWatchedDataForVideoStatisticsDaily::class,
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
        VideoCreated::class => [
            ChannelStatisticsDailyVideoCreated::class,
            SendNotificationOnVideoCreated::class,
        ],
        VideoUpdated::class => [
            SendNotificationOnVideoUpdated::class,
        ],
        VideoDeleted::class => [
            SendNotificationOnVideoDeleted::class,
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
