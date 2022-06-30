<?php

namespace App\Http\Controllers;

use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Channel;
use App\Models\ChannelStatisticsDaily;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Scopes\OrderDescScope;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneralController extends Controller
{
    public function home(Request $request)
    {
        $result = [];
        $user = auth('api')->user();

        // Trending Channels
        $trendingChannelIds = ChannelStatisticsDaily::selectRaw('SUM(subscribers_total) - SUM(unsubscribers_total) AS subscribers, channel_id')
            ->where('date', '>=', (Carbon::now())->subDays(30))
            ->groupBy('channel_id')
            ->withoutGlobalScope('orderByDate')
            ->orderBy('subscribers', 'DESC')
            ->take(15)
            ->pluck('channel_id')->toArray();

        $orderByTrendingChannelIds = implode(',', array_reverse($trendingChannelIds));

        $trendingChannels = Channel::published()
            ->when(!empty($orderByTrendingChannelIds), function ($q) use ($orderByTrendingChannelIds){
                $q->orderByRaw("FIELD(id,$orderByTrendingChannelIds) DESC, Created_at DESC");
            })
            ->take(15)
            ->get()
            ->append(['is_subscribed', 'subscribers_count']);
        $result['trending_channels'] = ChannelResource::collection($trendingChannels);

        // Trending Videos
        $trendingMediaIds = VideoStatisticsDaily::selectRaw('SUM(points) AS points, video_id')
            ->whereDate('date', '>=', (Carbon::now())->subDays(7)->format('Y-m-d'))
            ->groupBy('video_id')
            ->withoutGlobalScope('orderByDate')
            ->orderBy('points', 'DESC')
            //->take(15)
            ->pluck('video_id')
            ->toArray();

        $orderByTrendingMediaIds = implode(',', array_reverse($trendingMediaIds));

        $trendingVideos = Video::published()->typeVideo()
            ->withoutGlobalScope(OrderDescScope::class)
            ->when(!empty($orderByTrendingMediaIds), function ($q) use ($orderByTrendingMediaIds){
                $q->orderByRaw("FIELD(id,$orderByTrendingMediaIds) DESC, Created_at DESC");
            })
            ->take(15)
            ->with(['channel'])
            ->get()
            ->append(['is_bookmarked']);
        $result['trending_videos'] = VideoResource::collection($trendingVideos);

        $trendingPodcasts = Video::published()->typePodcast()
            ->withoutGlobalScope(OrderDescScope::class)
            ->when(!empty($orderByTrendingMediaIds), function ($q) use ($orderByTrendingMediaIds){
                $q->orderByRaw("FIELD(id,$orderByTrendingMediaIds) DESC, Created_at DESC");
            })
            ->take(15)
            ->with(['channel'])
            ->get()
            ->append(['is_bookmarked']);
        $result['trending_podcasts'] = VideoResource::collection($trendingPodcasts);

        // Latest Media On TC
        $latestMedia = Video::published()
            ->take(15)
            ->with(['channel'])
            ->get()
            ->append(['is_bookmarked']);
        $result['latest_media'] = VideoResource::collection($latestMedia);


        // Top Channels
        $topChannels = Channel::published()
            ->withCount('subscribers')
            ->orderBy('subscribers_count', 'desc')
            ->take(15)
            ->get()
            ->append('is_subscribed');
        $result['top_channels'] = ChannelResource::collection($topChannels);


        if ($user){
            // Videos For You
            $userFavoriteCoinIds = DB::table('crypto_currency_user')
                ->select('crypto_currency_id')
                ->where('user_id', $user->id)
                ->pluck('crypto_currency_id')
                ->toArray();

            if (empty($userFavoriteCoinIds)){
                $userFavoriteCoinIds = DB::table('crypto_currency_user')
                    ->selectRaw('COUNT(*) AS count, `crypto_currency_id`')
                    ->groupBy('crypto_currency_id')
                    ->orderBy('count','DESC')
                    ->take(15)
                    ->pluck('crypto_currency_id')->toArray();
            }

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

            $videosForYou = Video::published()
                ->where(function ($query) use ($userFavoriteCoinIds, $userFavoriteTagIds){
                    $query->whereHas('crypto_currencies', function ($query) use ($userFavoriteCoinIds){
                        $query->whereIn('id', $userFavoriteCoinIds);
                    })->orWhereHas('tags', function ($query) use ($userFavoriteTagIds){
                        $query->whereIn('id', $userFavoriteTagIds);
                    });
                })
                ->take(15)
                ->with(['channel'])
                ->get()
                ->append(['is_bookmarked']);

            $result['videos_for_you'] = VideoResource::collection($videosForYou);

            // My Subscriptions Videos
            $subscriptionsChannelIds = $user->subscribedChannels()->pluck('id')->toArray();
            $mySubscriptionsVideos = Video::published()
                ->whereIn('channel_id', $subscriptionsChannelIds)
                ->take(15)
                ->with(['channel'])
                ->get()
                ->append(['is_bookmarked']);
            $result['my_subscriptions_videos'] = VideoResource::collection($mySubscriptionsVideos);
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
}
