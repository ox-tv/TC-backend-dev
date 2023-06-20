<?php

namespace App\Http\Controllers;

use App\Http\Resources\Channel\ChannelHomeResource;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Video\VideoHomeResource;
use App\Http\Resources\Video\VideoResource;
use App\Mail\AdvertisementInquireMail;
use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use App\Models\ChannelStatisticsDaily;
use App\Models\Comment;
use App\Models\MonetizePoint;
use App\Models\Report;
use App\Models\Scopes\OrderDescScope;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\Video;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GeneralController extends Controller
{
    public function home(Request $request)
    {
        $result = [];
        $user = auth('api')->user();
        $videoIds =  Cache::remember('home_total_video_ids', 60 * 60 , function () {
            return Video::typeVideo()->published()->pluck('id')->toArray();
        });
        $podcastIds = Cache::remember('home_total_podcast_ids', 60 * 60 , function () {
            return Video::typePodcast()->published()->pluck('id')->toArray();
        });

        // Trending Channels
        $trendingChannels = Cache::remember('home_trending_channels', 60 * 60 , function () {
            $trendingChannelIds = Channel2StatisticsDaily::raw(function($collection) {
                return $collection->aggregate([
                    ['$match' => [
                        'date' => ['$gte'=> Channel2StatisticsDaily::fromDateTime(Carbon::now()->subDays(30))],
                    ]],
                    ['$group' => [
                        '_id' => '$channel_id',
                        'amount' => ['$sum' => ['$add' => [['$subtract'=> ['$subscribers_total', '$unsubscribers_total']], ['$subtract'=> ['$likes_total', '$dislikes_total']]]]],
                    ]],
                    ['$sort' => ['amount' => -1, '_id' => -1]],
                    ['$limit' => 15]
                ]);
            })->pluck('_id')->toArray();

            $orderByTrendingChannelIds = implode(',', array_reverse($trendingChannelIds));

            return Channel::published()
                ->orderByRaw((!empty($orderByTrendingChannelIds)?"FIELD(id,$orderByTrendingChannelIds) DESC, ": "") . "Created_at DESC")
                ->take(15)
                ->get()
                ->append(['is_subscribed', 'subscribers_count']);
        });

        $result['trending_channels'] = ChannelHomeResource::collection($trendingChannels);


        // Trending Videos
        $trendingVideos = Cache::remember('home_trending_videos', 60 * 60 , function () use ($videoIds) {
            $trendingVideoIds = Channel2StatisticsDaily::raw(function($collection) use ($videoIds) {
                return $collection->aggregate([
                    ['$match' => [
                        'date' => ['$gte'=> Channel2StatisticsDaily::fromDateTime(Carbon::now()->subDays(3))],
                        'video_id' => ['$in'=> $videoIds],
                    ]],
                    ['$group' => [
                        '_id' => '$video_id',
                        'amount' => [
                            '$sum' => [
                                '$add' => [
                                    '$views_total',
                                    ['$multiply' => [['$subtract' => ['$likes_total', '$dislikes_total']], 50]]
                                ]
                            ]
                        ],
                    ]],
                    ['$sort' => ['amount' => -1, '_id' => -1]],
                    ['$limit' => 24]
                ]);
            })->pluck('_id')->toArray();

            $orderByTrendingVideoIds = implode(',', array_reverse($trendingVideoIds));

            return Video::published()->typeVideo()
                ->withoutGlobalScope(OrderDescScope::class)
                ->orderByRaw((!empty($orderByTrendingVideoIds)?"FIELD(id,$orderByTrendingVideoIds) DESC, ": "") . "published_at DESC")
                ->take(24)
                ->with(['channel'])
                ->get()
                ->append(['is_bookmarked']);
        });

        $result['trending_videos'] = VideoHomeResource::collection($trendingVideos);


        // Trending Podcasts
        $trendingPodcasts = Cache::remember('home_trending_podcasts', 60 * 60 , function () use ($podcastIds) {
            $trendingPodcastIds = Channel2StatisticsDaily::raw(function($collection) use ($podcastIds) {
                return $collection->aggregate([
                    ['$match' => [
                        'date' => ['$gte'=> Channel2StatisticsDaily::fromDateTime(Carbon::now()->subDays(3))],
                        'video_id' => ['$in'=> $podcastIds],
                    ]],
                    ['$group' => [
                        '_id' => '$video_id',
                        'amount' => [
                            '$sum' => [
                                '$add' => [
                                    '$views_total',
                                    ['$multiply' => [['$subtract' => ['$likes_total', '$dislikes_total']], 50]]
                                ]
                            ]
                        ],
                    ]],
                    ['$sort' => ['amount' => -1, '_id' => -1]],
                    ['$limit' => 24]
                ]);
            })->pluck('_id')->toArray();

            $orderByTrendingPodcastIds = implode(',', array_reverse($trendingPodcastIds));

            return Video::published()->typePodcast()
                ->withoutGlobalScope(OrderDescScope::class)
                ->orderByRaw((!empty($orderByTrendingPodcastIds)?"FIELD(id,$orderByTrendingPodcastIds) DESC, ": "") . "published_at DESC")
                ->take(24)
                ->with(['channel'])
                ->get()
                ->append(['is_bookmarked']);
        });

        $result['trending_podcasts'] = VideoHomeResource::collection($trendingPodcasts);


        // Latest Media On TC
        $latestMedia = Cache::remember('home_latest_media', 60 * 60 , function () {
            return Video::published()
                ->take(24)
                ->with(['channel'])
                ->withoutGlobalScope(OrderDescScope::class)
                ->orderBy('published_at', 'desc')
                ->get()
                ->append(['is_bookmarked']);
        });

        $result['latest_media'] = VideoHomeResource::collection($latestMedia);


        // Top Channels
        $topChannels = Cache::remember('home_top_channels', 60 * 60 , function () {
            return Channel::published()
                ->withCount('subscribers')
                ->orderBy('subscribers_count', 'desc')
                ->take(15)
                ->get()
                ->append('is_subscribed');
        });

        $result['top_channels'] = ChannelHomeResource::collection($topChannels);


        if ($user){
            // Videos For You
            $userFavoriteTagIds = DB::table('tag_user')
                ->select('tag_id')
                ->where('user_id', $user->id)
                ->pluck('tag_id')
                ->toArray();

            if (empty($userFavoriteTagIds)){
                $userFavoriteTagIds = DB::table('tag_video')
                    ->selectRaw('COUNT(*) AS count, `tag_id`')
                    ->groupBy('tag_id')
                    ->orderBy('count','DESC')
                    ->take(15)
                    ->pluck('tag_id')->toArray();
            }

            $customFeedSetting = $user->meta()->where('key', UserMeta::CustomFeedSetting)->first();
            $userFavoriteCoinIds = [];

            if (!$customFeedSetting || $customFeedSetting->value['crypto_currencies_content_based']){
                $userFavoriteCoinIds = DB::table('crypto_currency_user')
                    ->select('crypto_currency_id')
                    ->where('user_id', $user->id)
                    ->pluck('crypto_currency_id')
                    ->toArray();
            }

            if (empty($userFavoriteCoinIds)){
                $userFavoriteCoinIds = DB::table('crypto_currency_user')
                    ->selectRaw('COUNT(*) AS count, `crypto_currency_id`')
                    ->groupBy('crypto_currency_id')
                    ->orderBy('count','DESC')
                    ->take(15)
                    ->pluck('crypto_currency_id')->toArray();
            }

            $videosForYou = Video::published()
                ->where(function ($query) use ($userFavoriteCoinIds, $userFavoriteTagIds){
                    $query->whereHas('tags', function ($query) use ($userFavoriteTagIds){
                        $query->whereIn('id', $userFavoriteTagIds);
                    })->orWhereHas('crypto_currencies', function ($query) use ($userFavoriteCoinIds){
                        $query->whereIn('id', $userFavoriteCoinIds);
                    });
                })
                ->take(24)
                ->with(['channel'])
                ->withoutGlobalScope(OrderDescScope::class)
                ->orderBy('published_at', 'desc')
                ->get()
                ->append(['is_bookmarked']);

            $result['videos_for_you'] = VideoHomeResource::collection($videosForYou);

            // My Subscriptions Videos
            $subscriptionsChannelIds = $user->subscribedChannels()->pluck('id')->toArray();
            $mySubscriptionsVideos = Video::published()
                ->whereIn('channel_id', $subscriptionsChannelIds)
                ->take(24)
                ->with(['channel'])
                ->orderBy('published_at', 'desc')
                ->get()
                ->append(['is_bookmarked']);
            $result['my_subscriptions_videos'] = VideoHomeResource::collection($mySubscriptionsVideos);
        }

        return $result;
    }

    public function adminDashboard(Request $request)
    {
        $result = [
            'total_registered_users' => 0,
            'new_registered_users_this_month' => 0,
            'total_channels' => 0,
            'new_channels_this_month' => 0,
            'total_videos' => 0,
            'new_videos_this_month' => 0,
            'videos_hours_minutes' => 0,
            'videos_sizes_MB' => 0,
            'total_hero_members' => 0,
            'new_hero_members_this_month' => 0,
            'total_expired_members' => 0,
            'expired_members_this_month' => 0,
            'reported_comments' => 0,
            'reported_videos' => 0,
        ];

        $now = Carbon::now();
        $startOfMonth = (Carbon::now())->startOfMonth();

        $result['total_registered_users'] = User::withTrashed()->count();
        $result['new_registered_users_this_month'] = User::withTrashed()
            ->whereDate('created_at','>=', $startOfMonth->format('Y-m-d H:i:s'))
            ->count();

        $result['total_channels'] = Channel::withTrashed()->count();
        $result['new_channels_this_month'] = Channel::withTrashed()
            ->whereDate('created_at','>=', $startOfMonth->format('Y-m-d H:i:s'))
            ->count();

        $result['total_videos'] = Video::withTrashed()->count();
        $result['new_videos_this_month'] = Video::withTrashed()
            ->whereDate('created_at','>=', $startOfMonth->format('Y-m-d H:i:s'))
            ->count();

        $result['videos_hours_minutes'] = round(Video::withTrashed()->sum('duration') / 60);
        $result['videos_sizes_MB'] = rand(1,99);

        $result['total_hero_members'] = User::withTrashed()->isHero()->count();
        $result['new_hero_members_this_month'] = User::withTrashed()->isHero()->whereHas('pricing', function ($q) use ($startOfMonth){
            $q->where('pricing_user.created_at', '>=', $startOfMonth->format('Y-m-d H:i:s'));
        })->count();


        $result['total_expired_members'] = User::withTrashed()->whereNotNull('hero_due_at')
            ->whereDate('hero_due_at','<', $now->format('Y-m-d H:i:s'))->count();
        $result['expired_members_this_month'] = User::withTrashed()
            ->whereDate('hero_due_at','>=', $startOfMonth->format('Y-m-d H:i:s'))
            ->whereDate('hero_due_at','<', $now->format('Y-m-d H:i:s'))
            ->count();

        $result['reported_comments'] = Report::where('reportable_type', Comment::class)->count();
        $result['reported_videos'] = Report::where('reportable_type', Video::class)->count();


        return $result;
    }

    public function publisherDashboard(Request $request)
    {
        $result = [
            'overview' => [
                'points' => 0,
                'watch_time_total' => 0,
                'subscribers_total' => 0,
                'views_total' => 0,
                'likes_total' => 0,
                'upload_videos_total' => 0,
                'published_videos' => 0,
            ],
            'statistics' => [],
        ];

        $channel = auth('api')->user()->channel;

        // Overview Statistics by channel id
        $channelStatisticsQuery = channel2StatisticsDaily::where('channel_id', $channel->id);

        $result['overview']['points'] = intval(MonetizePoint::where('channel_id', $channel->id)->whereNotNull('activated_at')->sum('amount'));
        $result['overview']['watch_time_total'] = intval($channelStatisticsQuery->sum('watch_time_total'));
        $result['overview']['subscribers_total'] = intval($channelStatisticsQuery->sum('subscribers_total')) - intval($channelStatisticsQuery->sum('unsubscribers_total'));
        $result['overview']['views_total'] = intval($channelStatisticsQuery->sum('views_total'));
        $result['overview']['likes_total'] = natural_intval($channelStatisticsQuery->sum('likes_total'));
        $result['overview']['upload_videos_total'] = intval($channelStatisticsQuery->sum('upload_videos_total'));
        $result['overview']['published_videos'] = intval($channelStatisticsQuery->sum('published_videos')) - intval($channelStatisticsQuery->sum('unpublished_videos'));


        // Statistics by channel id
        $filters = $request->get('filters', []);
        $period = Arr::get($filters, 'statistics_period', 'last_30d');

        switch ($period) {
            case 'this_week';
                $from = Carbon::now()->startOfWeek();
                $to = Carbon::now()->endOfWeek();
                break;
            case 'last_week';
                $from = Carbon::now()->subWeek()->startOfWeek();
                $to = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'last_month';
                $from = Carbon::now()->startOfMonth()->subMonthsNoOverflow();
                $to = Carbon::now()->subMonthsNoOverflow()->endOfMonth();
                break;
            case 'this_year';
                $from = Carbon::now()->startOfYear();
                $to = Carbon::now()->endOfYear();
                break;
            case 'this_month';
                $from = Carbon::now()->startOfMonth();
                $to = Carbon::now()->endOfMonth();
                break;

            case 'last_7d';
                $from = Carbon::now()->subDays(7)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_14d';
                $from = Carbon::now()->subDays(14)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_90d';
                $from = Carbon::now()->subDays(90)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_180d';
                $from = Carbon::now()->subDays(180)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_365d';
                $from = Carbon::now()->subDays(365)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_30d';
            default;
                $from = Carbon::now()->subDays(30)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
        }

        $result['statistics'] = in_array($period, ['this_year', 'last_365d', 'last_180d'])? $this->monthlyStatistics($channel, $from, $to) : $this->dailyStatistics($channel, $from, $to);

        return response()->json($result);
    }

    private function dailyStatistics($channel, $from, $to): array
    {
        $statistics = [];
        $periods = CarbonPeriod::create($from, '1 day', $to);

        foreach ($periods as $day) {
            $channelStatisticsQuery = channel2StatisticsDaily::where('channel_id', $channel->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')))->get();

            $monetizePointsQuery = MonetizePoint::where('channel_id', $channel->id)
                ->whereNotNull('activated_at')
                ->where('date', Carbon::parse($day->format('Y-m-d')))->get();

            $statistics[$day->format('Y-m-d')] = [
                'date' => $day->format('Y-m-d'),
                'points' => intval($monetizePointsQuery->sum('amount')),
                'views_total' => intval($channelStatisticsQuery->sum('views_total')),
                'likes_total' => natural_intval($channelStatisticsQuery->sum('likes_total')),
                'dislikes_total' => natural_intval($channelStatisticsQuery->sum('dislikes_total')),
                'comments_total' => intval($channelStatisticsQuery->sum('comments_total')),
                'watch_time_total' => intval($channelStatisticsQuery->sum('watch_time_total')),
                'subscribers_total' => natural_intval($channelStatisticsQuery->sum('subscribers_total')),
                'unsubscribers_total' => natural_intval($channelStatisticsQuery->sum('unsubscribers_total')),
                'upload_videos_total' => intval($channelStatisticsQuery->sum('upload_videos_total')),
                'published_videos' => intval($channelStatisticsQuery->sum('published_videos')),
                'unpublished_videos' => intval($channelStatisticsQuery->sum('unpublished_videos')),
            ];
        }

        return $statistics;
    }

    private function monthlyStatistics($channel, $from, $to): array
    {
        $statistics = [];
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $date = $month->copy()->startOfMonth()->format("Y-m-d");

            $channelStatisticsQuery = channel2StatisticsDaily::where('channel_id', $channel->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $monetizePointQuery = MonetizePoint::where('channel_id', $channel->id)
                ->whereNotNull('activated_at')
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $statistics[$date] = [
                'date' => $date,
                'points' => intval($monetizePointQuery->sum('amount')),
                'views_total' => intval($channelStatisticsQuery->sum('views_total')),
                'likes_total' => natural_intval($channelStatisticsQuery->sum('likes_total')),
                'dislikes_total' => natural_intval($channelStatisticsQuery->sum('dislikes_total')),
                'comments_total' => intval($channelStatisticsQuery->sum('comments_total')),
                'watch_time_total' => intval($channelStatisticsQuery->sum('watch_time_total')),
                'subscribers_total' => natural_intval($channelStatisticsQuery->sum('subscribers_total')),
                'unsubscribers_total' => natural_intval($channelStatisticsQuery->sum('unsubscribers_total')),
                'upload_videos_total' => intval($channelStatisticsQuery->sum('upload_videos_total')),
                'published_videos' => intval($channelStatisticsQuery->sum('published_videos')),
                'unpublished_videos' => intval($channelStatisticsQuery->sum('unpublished_videos')),
            ];
        }

        return $statistics;
    }

    public function advertisementInquireForm(Request $request)
    {
        $request->validate([
            'c_p_name' => ['required'],
            'c_p_website' => ['required'],
            'full_name' => ['required'],
            'email' => ['required', 'email'],
            'estimated_budget' => ['required'],
        ]);

        $data = $request->all();

        $data['c_p_channel_url'] = $request->get('c_p_channel_url', ' - ');

        $destinationMail = config('general.ADVERTISEMENT_INQUIRE_MAIL_TO');

        Mail::to($destinationMail)
            ->queue(new AdvertisementInquireMail($data));

        return response()->json(['status' => 'ok']);
    }
}
