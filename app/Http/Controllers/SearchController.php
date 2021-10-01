<?php

namespace App\Http\Controllers;

use App\Http\Resources\Channel\ChannelItem;
use App\Http\Resources\Subtitle\SubtitleItem;
use App\Http\Resources\Video\VideoItem;
use App\Models\Channel;
use App\Models\Language;
use App\Models\Subtitle;
use App\Models\Video;
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

        return VideoItem::collection($videoQuery->paginate())
            ->additional(['channels' => ChannelItem::collection($channelQuery->take(10)->get())]);
    }
}
