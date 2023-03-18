<?php

namespace App\Providers;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\Channels\ChannelImportRequestCreated;
use App\Events\Channels\ChannelUpdated;
use App\Events\ChannelSubscribed;
use App\Events\CommentLiked;
use App\Events\Comments\CommentCreated;
use App\Events\Messages\MessageCreatedByAdmin;
use App\Events\Messages\MessageCreatedByUser;
use App\Events\Messages\MessageRepliedByAdmin;
use App\Events\Messages\MessageRepliedByUser;
use App\Events\Publisher\NewPublisherRequested;
use App\Events\Publisher\PublisherRequestApproved;
use App\Events\Publisher\PublisherRequestRejected;
use App\Events\Report\ReportCreated;
use App\Events\User\BuyingHeroMemberShipCompleted;
use App\Events\User\CustomFeedFilled;
use App\Events\UserVerified;
use App\Events\VideoCreated;
use App\Events\VideoDeleted;
use App\Events\VideoUpdated;
use App\Events\VideoWasHidden;
use App\Events\VideoWasUnHidden;
use App\Events\VideoWatched;
use App\Listeners\Channels\MonetizePointsForChannelSubscribed;
use App\Listeners\Channels\SendEmailOnChannelImportRequestCompleted;
use App\Listeners\Channels\SendNotificationOnChannelImportRequestAccepted;
use App\Listeners\Channels\SendNotificationOnChannelImportRequestCompleted;
use App\Listeners\Channels\SendNotificationOnChannelImportRequestCreated;
use App\Listeners\Channels\SendNotificationOnChannelUpdated;
use App\Listeners\ChannelStatisticsDailySubscribed;
use App\Listeners\ChannelStatisticsDailyVideoCreated;
use App\Listeners\ChannelStatisticsDailyVideoUpdated;
use App\Listeners\CommentLikedDataForUserStatisticsDaily;
use App\Listeners\Comments\LogCommentLikedOnceForCommentLiked;
use App\Listeners\Comments\LoyaltyPointsForCommentLiked;
use App\Listeners\Comments\SendNotificationOnCommentCreated;
use App\Listeners\Comments\TokenPointsForCommentCreated;
use App\Listeners\Comments\TokenPointsForCommentLiked;
use App\Listeners\HeroMemberShip\TokenPointsForBuyingHeroMemberShipCompleted;
use App\Listeners\Messages\SendNotificationOnMessageCreatedByAdmin;
use App\Listeners\Messages\SendNotificationOnMessageCreatedByUser;
use App\Listeners\Messages\SendNotificationOnMessageRepliedByAdmin;
use App\Listeners\Messages\SendNotificationOnMessageRepliedByUser;
use App\Listeners\Publisher\SendEmailOnPublisherRequestApproved;
use App\Listeners\Publisher\SendEmailOnPublisherRequestRejected;
use App\Listeners\Publisher\SendNotificationOnNewPublisherRequested;
use App\Listeners\Publisher\SendNotificationOnPublisherRequestApproved;
use App\Listeners\Publisher\SendNotificationOnPublisherRequestRejected;
use App\Listeners\Report\SendNotificationOnReportCreated;
use App\Listeners\SendNotificationOnUserVerified;
use App\Listeners\SendNotificationOnVideoCreated;
use App\Listeners\SendNotificationOnVideoDeleted;
use App\Listeners\SendNotificationOnVideoUpdated;
use App\Listeners\SendNotificationOnVideoWasHidden;
use App\Listeners\SendNotificationOnVideoWasUnHidden;
use App\Listeners\StripeWebhookHandledListener;
use App\Listeners\User\LoyaltyPointsForUserVerified;
use App\Listeners\User\MonetizePointsForUserVerified;
use App\Listeners\User\TokenPointsForCustomFeedFilled;
use App\Listeners\User\TokenPointsForUserVerified;
use App\Listeners\UserVerifiedDataForUserStatisticsDaily;
use App\Listeners\Video\LoyaltyPointsForVideoWatched;
use App\Listeners\Video\MonetizePointsForVideoLiked;
use App\Listeners\Video\MonetizePointsForVideoViewed;
use App\Listeners\Video\TokenPointsForVideoCreated;
use App\Listeners\Video\TokenPointsForVideoUpdated;
use App\Listeners\Video\TokenPointsForVideoWatched;
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
        // User
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserVerified::class => [
            MonetizePointsForUserVerified::class,
            LoyaltyPointsForUserVerified::class,
            TokenPointsForUserVerified::class,
            UserVerifiedDataForUserStatisticsDaily::class,
            SendNotificationOnUserVerified::class,
        ],
        CustomFeedFilled::class => [
            TokenPointsForCustomFeedFilled::class,
        ],

        // Publisher
        PublisherRequestApproved::class => [
            SendNotificationOnPublisherRequestApproved::class,
            SendEmailOnPublisherRequestApproved::class,
        ],
        PublisherRequestRejected::class => [
            SendNotificationOnPublisherRequestRejected::class,
            SendEmailOnPublisherRequestRejected::class,
        ],
        NewPublisherRequested::class => [
            SendNotificationOnNewPublisherRequested::class,
        ],

        // HeroMemberShip
        BuyingHeroMemberShipCompleted::class => [
            TokenPointsForBuyingHeroMemberShipCompleted::class,
        ],

        // Channel
        ChannelSubscribed::class => [
            MonetizePointsForChannelSubscribed::class,
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
        CommentCreated::class => [
            VideoStatisticsDailyCommented::class,
            SendNotificationOnCommentCreated::class,
            TokenPointsForCommentCreated::class,
        ],
        CommentLiked::class => [
            CommentLikedDataForUserStatisticsDaily::class,
            LoyaltyPointsForCommentLiked::class,
            TokenPointsForCommentLiked::class, // Must be fired before `LogCommentLikedOnceForCommentLiked` listener
            LogCommentLikedOnceForCommentLiked::class,
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
        ReportCreated::class => [
            SendNotificationOnReportCreated::class,
        ],

        // Videos
        VideoCreated::class => [
            ChannelStatisticsDailyVideoCreated::class,
            SendNotificationOnVideoCreated::class,
            TokenPointsForVideoCreated::class,
        ],
        VideoUpdated::class => [
            SendNotificationOnVideoUpdated::class,
            ChannelStatisticsDailyVideoUpdated::class,
            TokenPointsForVideoUpdated::class,
        ],
        VideoDeleted::class => [
            SendNotificationOnVideoDeleted::class,
        ],
        VideoWasHidden::class => [
            SendNotificationOnVideoWasHidden::class,
        ],
        VideoWasUnHidden::class => [
            SendNotificationOnVideoWasUnHidden::class,
        ],
        VideoLiked::class => [
            MonetizePointsForVideoLiked::class,
            VideoStatisticsDailyLiked::class,
            VideoLikedDataForUserStatisticsDaily::class,
        ],
        VideoViewed::class => [
            MonetizePointsForVideoViewed::class,
            VideoStatisticsDailyIncreaseView::class,
            VideoViewedDataForUserStatisticsDaily::class,
        ],
        VideoWatched::class => [
            VideoWatchedDataForUserStatisticsDaily::class,
            LoyaltyPointsForVideoWatched::class,
            TokenPointsForVideoWatched::class,
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
