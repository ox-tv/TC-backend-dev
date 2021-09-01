<?php

namespace App\Http\Controllers;

use App\Http\Resources\Category\CategoryItem;
use App\Http\Resources\Video\HomeVideoCollection;
use App\Http\Resources\Video\HomeVideoItem;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoSummaryCollection;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Report;
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
        $user = auth('api')->user();

        // Get user favorite coin videos else trending coin videos
        if ($user){
            $coin_ids = $user->favoriteCryptoCurrencies()->pluck('id')->toArray();
        }
        if (!$user || empty($coin_ids)){
            $coin_ids = DB::table('crypto_currency_user')
                ->selectRaw('COUNT(*) AS count, `crypto_currency_id`')
                ->groupBy('crypto_currency_id')
                ->orderBy('count','DESC')
                ->take(20)
                ->pluck('crypto_currency_id')->toArray();
        }

        $favoriteCoinVideos = Video::published()
            ->whereHas('crypto_currencies', function ($query) use ($coin_ids){
                $query->whereIn('id', $coin_ids);
            })->take(15)->get();


        // Get user subscribe channel videos else latest videos
        $latestVideosQuery = Video::published();

        if ($user){
            $subscribedChannels = $user->subscribedChannels()->pluck('id')->toArray();

            if (!empty($subscribedChannels)){
                $latestVideosQuery->whereIn('channel_id', $subscribedChannels);
            }
        }

        $latestVideos = $latestVideosQuery->take(15)->get();


        // Get popular videos
        $popularVideoIds = VideoStatisticsDaily::selectRaw('SUM(points) AS points, video_id')
            ->whereDate('date', '>=', (Carbon::now())->subDays(30)->format('Y-m-d'))
            ->groupBy('video_id')
            ->withoutGlobalScope('orderByDate')
            ->orderBy('points', 'DESC')
            ->take(100)
            ->pluck('video_id')->toArray();

        $orderByPopular = implode(',', array_reverse($popularVideoIds));

        $popularVideos = Video::published()
            ->orderByRaw("FIELD(id,$orderByPopular) DESC, Created_at DESC")
            ->take(15)->get();


        return response()->json([
            'latest_videos' => HomeVideoItem::collection($latestVideos),
            'popular_videos' => HomeVideoItem::collection($popularVideos),
            'favorite_coin_videos' => HomeVideoItem::collection($favoriteCoinVideos),
        ]);
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
