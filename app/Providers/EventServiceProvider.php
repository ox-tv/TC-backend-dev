<?php

namespace App\Providers;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\Channels\ChannelImportRequestCreated;
use App\Events\Channels\ChannelUpdated;
use App\Events\ChannelSubscribed;
use App\Events\CommentLiked;
use App\Events\Messages\MessageCreatedByAdmin;
use App\Events\Messages\MessageCreatedByUser;
use App\Events\Messages\MessageRepliedByAdmin;
use App\Events\Messages\MessageRepliedByUser;
use App\Events\UserVerified;
use App\Events\VideoCommented;
use App\Events\VideoCreated;
use App\Events\VideoDeleted;
use App\Events\VideoUpdated;
use App\Events\VideoWasHidden;
use App\Events\VideoWatched;
use App\Listeners\Channels\SendEmailOnChannelImportRequestCompleted;
use App\Listeners\Channels\SendNotificationOnChannelImportRequestAccepted;
use App\Listeners\Channels\SendNotificationOnChannelImportRequestCompleted;
use App\Listeners\Channels\SendNotificationOnChannelImportRequestCreated;
use App\Listeners\Channels\SendNotificationOnChannelUpdated;
use App\Listeners\ChannelStatisticsDailySubscribed;
use App\Listeners\ChannelStatisticsDailyVideoCreated;
use App\Listeners\CommentLikedDataForUserStatisticsDaily;
use App\Listeners\Messages\SendNotificationOnMessageCreatedByAdmin;
use App\Listeners\Messages\SendNotificationOnMessageCreatedByUser;
use App\Listeners\Messages\SendNotificationOnMessageRepliedByAdmin;
use App\Listeners\Messages\SendNotificationOnMessageRepliedByUser;
use App\Listeners\SendNotificationOnVideoCreated;
use App\Listeners\SendNotificationOnVideoDeleted;
use App\Listeners\SendNotificationOnVideoUpdated;
use App\Listeners\SendNotificationOnVideoWasHidden;
use App\Listeners\StripeWebhookHandledListener;
use App\Listeners\UserVerifiedDataForUserStatisticsDaily;
use App\Listeners\VideoLikedDataForUserStatisticsDaily;
use App\Listeners\VideoStatisticsDailyCommented;
use App\Listeners\VideoViewedDataForUserStatisticsDaily;
use App\Listeners\VideoWatchedDataForUserStatisticsDaily;
use App\Listeners\VideoWatchedDataForVideoStatisticsDaily;
use App\Models\Channel;
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
        // User
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserVerified::class => [
            UserVerifiedDataForUserStatisticsDaily::class,
        ],

        // Channel
        ChannelSubscribed::class => [
            ChannelStatisticsDailySubscribed::class,
        ],
        ChannelImportRequestCreated::class => [
            SendNotificationOnChannelImportRequestCreated::class,
        ],
        ChannelUpdated::class => [
            SendNotificationOnChannelUpdated::class,
        ],
        ChannelImportRequestAccepted::class => [
            SendNotificationOnChannelImportRequestAccepted::class,
        ],
        ChannelImportRequestCompleted::class => [
            SendNotificationOnChannelImportRequestCompleted::class,
            SendEmailOnChannelImportRequestCompleted::class,
        ],

        // Payment
        WebhookReceived::class => [
            StripeWebhookHandledListener::class,
        ],

        // Comments
        VideoCommented::class => [
            VideoStatisticsDailyCommented::class,
        ],
        CommentLiked::class => [
            CommentLikedDataForUserStatisticsDaily::class,
        ],

        // Messages
        MessageCreatedByAdmin::class => [
            SendNotificationOnMessageCreatedByAdmin::class,
        ],
        MessageCreatedByUser::class => [
            SendNotificationOnMessageCreatedByUser::class,
        ],
        MessageRepliedByAdmin::class => [
            SendNotificationOnMessageRepliedByAdmin::class,
        ],
        MessageRepliedByUser::class => [
            SendNotificationOnMessageRepliedByUser::class,
        ],

        // Reports


        // Videos
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
        VideoWasHidden::class => [
            SendNotificationOnVideoWasHidden::class,
        ],
        VideoLiked::class => [
            VideoStatisticsDailyLiked::class,
            VideoLikedDataForUserStatisticsDaily::class,
        ],
        VideoViewed::class => [
            VideoStatisticsDailyIncreaseView::class,
            VideoViewedDataForUserStatisticsDaily::class,
        ],
        VideoWatched::class => [
            VideoWatchedDataForUserStatisticsDaily::class,
            VideoWatchedDataForVideoStatisticsDaily::class,
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
