<?php

namespace App\Http\Controllers;

use App\Http\Resources\Category\CategoryItem;
use App\Http\Resources\Video\HomeVideoCollection;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoSummaryCollection;
use App\Models\Category;
use App\Models\Video;
use Illuminate\Http\Request;

class GeneralController extends Controller
{
    public function home(Request $request){

        $latestVideos = HomeVideoCollection::make(Video::published()->latest()->take(15)->get());

        $featuredCategories = Category::whereHas("main_videos")->featured()->get();
        $videoByCategories = [];

        foreach ($featuredCategories as $featuredCategory){
            $videoByCategories[] = [
                'videos' => HomeVideoCollection::make($featuredCategory->main_videos()->published()->take(15)->get()),
                'category' => CategoryItem::make($featuredCategory)
            ];
        }

        return response()->json([
            'latest_videos' => $latestVideos,
            'video_by_categories' => $videoByCategories
        ]);
    }
}
