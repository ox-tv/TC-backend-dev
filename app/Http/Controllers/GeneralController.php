<?php

namespace App\Http\Controllers;

use App\Http\Resources\Category\CategoryItem;
use App\Http\Resources\Video\HomeVideoCollection;
use App\Http\Resources\Video\HomeVideoItem;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoSummaryCollection;
use App\Models\Category;
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
        }else{
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
                $latestVideosQuery->whereHas('channels', function ($query) use ($subscribedChannels){
                    $query->whereIn('id', $subscribedChannels);
                });
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
}
