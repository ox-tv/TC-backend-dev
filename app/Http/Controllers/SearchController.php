<?php

namespace App\Http\Controllers;

use App\Http\Resources\Channel\ChannelItem;
use App\Http\Resources\Subtitle\SubtitleItem;
use App\Http\Resources\Video\VideoItem;
use App\Models\Channel;
use App\Models\Language;
use App\Models\Subtitle;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Done\Subtitles\Subtitles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SearchController extends Controller
{
    public function index($keyword)
    {
        // Get videos
        $videoQuery = Video::published();

        $videoQuery->where(function ($query) use ($keyword){
            $query->where(function ($query) use ($keyword){
                $query->SearchTitle($keyword);
            })->orWhere(function ($query) use ($keyword){
                $query->SearchDescription($keyword);
            });
        });

        // Get channels
        $channelQuery = Channel::published();

        $channelQuery->where(function ($query) use ($keyword) {
            $query->SearchByOwner($keyword);
        })->orWhere(function ($query) use($keyword) {
            $query->SearchTitle($keyword);
        });

        $additionalData = [
            'channels' => ChannelItem::collection($channelQuery->take(10)->get()),
        ];

        // Get Popular Videos if Search Result is Empty
        if ($videoQuery->count() == 0){
            $popularVideoIds = VideoStatisticsDaily::selectRaw('SUM(points) AS points, video_id')
                ->whereDate('date', '>=', (Carbon::now())->subDays(30)->format('Y-m-d'))
                ->groupBy('video_id')
                ->withoutGlobalScope('orderByDate')
                ->orderBy('points', 'DESC')
                ->take(100)
                ->pluck('video_id')->toArray();

            $orderByPopular = implode(',', array_reverse($popularVideoIds));

            $additionalData['suggested_videos'] = VideoItem::collection(Video::published()
                ->orderByRaw("FIELD(id,$orderByPopular) DESC, Created_at DESC")
                ->take(15)->get());
        }

        return VideoItem::collection($videoQuery->paginate())
            ->additional($additionalData);
    }
}
