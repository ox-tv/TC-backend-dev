<?php

namespace App\Http\Controllers;

use App\Http\Resources\Category\CategoryItem;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoSummaryCollection;
use App\Models\Category;
use App\Models\Video;
use Illuminate\Http\Request;

class GeneralController extends Controller
{
    public function home(Request $request){
        $latestVideos = VideoSummaryCollection::make(Video::published()->latest()->paginate());

        $featuredCategories = Category::hasVideo()->featured()->get();

        $videoByCategories = [];

        foreach ($featuredCategories as $featuredCategory){
            $videoByCategories[] = [
                'videos' => VideoSummaryCollection::make($featuredCategory->videos()->published()->paginate()),
                'category' => CategoryItem::make($featuredCategory)
            ];
        }

        return response()->json([
            'latest_videos' => $latestVideos,
            'video_by_categories' => $videoByCategories
        ]);

    }
}
