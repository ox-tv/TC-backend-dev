<?php

namespace App\Http\Controllers;

use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Video\VideoResource;
use App\Models\Channel;
use App\Models\MonetizePoint;
use App\Models\Scopes\OrderDescScope;
use App\Models\Tag;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SearchController extends Controller
{
    public function index(Request $request, $keyword)
    {
        $perPage = $request->get('per_page') ?: 15;

        $filters = $request->get('filters', []);
        $timeFilter = Arr::get($filters, 'time');
        $mediaTypeFilter = Arr::get($filters, 'media_type');

        $sort = $request->get('sort');

        // Get videos
        $videoQuery = Video::published();

        $relatedTagIds = [];
        if (strlen($keyword) >= 3){
            $relatedTagIds = Tag::searchName($keyword)->pluck('id')->toArray();
        }

        $videoQuery->where(function ($query) use ($keyword, $relatedTagIds){
            $query->where(function ($query) use ($keyword){
                $query->where(function ($query) use ($keyword){
                    $query->where(function ($query) use ($keyword){
                        $query->SearchTitle($keyword);
                    })->orWhere(function ($query) use ($keyword){
                        $query->SearchDescription($keyword);
                    });
                });
            })->orWhere(function ($query) use ($keyword, $relatedTagIds){
                $query->whereHas('tags', function ($query) use ($relatedTagIds){
                    $query->whereIn('id', $relatedTagIds);
                });
            });
        });

        // Check filters
        if($mediaTypeFilter && !empty(array_flip(Video::MEDIA_TYPE_TEXT)[$mediaTypeFilter])){
            $videoQuery->where('media_type', array_flip(Video::MEDIA_TYPE_TEXT)[$mediaTypeFilter]);
        }

        if($timeFilter){
            switch ($timeFilter){
                case 'last_hour':{
                    $videoQuery->lastHour();
                    break;
                }
                case 'last_day':{
                    $videoQuery->lastDay();
                    break;
                }
                case 'last_week':{
                    $videoQuery->lastweek();
                    break;
                }
                case 'last_month':{
                    $videoQuery->lastMonth();
                    break;
                }
                case 'last_season':{
                    $videoQuery->lastSeason();
                    break;
                }
                default:{

                }
            }
        }

        if($sort === 'most_liked'){
            $videoQuery->withCount(['likedBy', 'dislikedBy'])->orderByRaw('(liked_by_count - disliked_by_count) DESC');
        }elseif ($sort === 'most_viewed'){
            $videoQuery->orderBy('view_count', 'desc');
        }elseif ($sort === 'most_commented'){
            $videoQuery->withCount('comments')->orderBy('comments_count', 'desc');
        }else{
            $videoQuery->withoutGlobalScope(OrderDescScope::class)->orderBy('published_at', 'desc');
        }

        // Get channels
        $channelQuery = Channel::published();

        $channelQuery->where(function ($query) use ($keyword) {
            $query->SearchByOwner($keyword);
        })->orWhere(function ($query) use($keyword) {
            $query->SearchTitle($keyword);
        });

        $channels = $channelQuery->take(10)->get();
        $channels->append(['is_subscribed', 'subscribers_count']);

        $additionalData = [
            'channels' => ChannelResource::collection($channels),
        ];

        // Get Popular Videos if Search Result is Empty
        if ($videoQuery->count() == 0){

            $popularVideoIds = MonetizePoint::raw(function($collection) {
                return $collection->aggregate([
                    ['$match' => [
                        'related_to_type' => Video::class,
                        'date' => ['$gte'=> MonetizePoint::fromDateTime(Carbon::now()->subDays(30))],
                    ]],
                    ['$group' => [
                        '_id' => '$related_to_id',
                        'amount' => ['$sum' => '$amount'],
                    ]],
                    ['$sort' => ['amount' => -1]],
                    ['$limit' => 15]
                ]);
            })->pluck('_id')->toArray();

            $orderByPopular = implode(',', array_reverse($popularVideoIds));

            $suggestedResult = Video::published()
                ->orderByRaw("FIELD(id,$orderByPopular) DESC, published_at DESC")
                ->take(15)->get();

            $suggestedResult->load(['channel'])->append(['is_bookmarked']);

            $additionalData['suggested_videos'] = VideoResource::collection($suggestedResult);
        }

        $searchResult = $videoQuery->paginate($perPage);

        $searchResult->load(['channel'])->append(['is_bookmarked']);

        return VideoResource::collection($searchResult)
            ->additional($additionalData);
    }
}
