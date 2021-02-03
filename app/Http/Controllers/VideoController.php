<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoComment;
use App\Http\Requests\VideoStore;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoItem;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return VideoCollection
     */
    public function index(Request $request)
    {

        $publisherVideos = $request->is('api/publisher/videos');

        if($publisherVideos){
            $query = Video::mine();
        }else{
            $query = Video::published();
        }

        $filters = $request->get('filters', []);

        $timeFilter = Arr::get($filters, 'time');
        $searchFilter = Arr::get($filters, 'search');
        $categoryId = Arr::get($filters, 'category_id');

        if($timeFilter == 'week'){
            $query->week();
        }

        if($searchFilter){
            $query->where(function ($query) use ($searchFilter){
                $query->SearchTitle($searchFilter);
            })->orWhere(function ($query) use ($searchFilter){
                $query->SearchDescription($searchFilter);
            });
        }

        if($categoryId){
            $query->filterCategory($categoryId);
        }

        $videos = $query->paginate();

        return new VideoCollection($videos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return VideoItem
     */
    public function store(VideoStore $request)
    {

        $video = new Video();

        $video->title = $request->get('title');
        $video->slug = Str::slug($request->get('title'));
        $video->description = $request->get('description');

        if($request->get('youtube_link')){
            $video->youtube_link = $request->get('youtube_link');

            $video->upload_method = Video::UPLOAD_METHOD_YOUTUBE;

        }else if($request->file('video')){ // adding file to video
            $videoFile = Storage::disk('videos')->put('/', $request->file('video'));

            $video->file_path = $videoFile;
        }

        // adding user to video
        $video->user_id = auth()->user()->id;

        // thumbnail
        $video->thumbnail = $request->get('thumbnail');

        $video->save();

        // adding categories
        if($request->get('categories')){
            $video->categories()->saveMany(Category::whereIn('id', $request->get('categories'))->get());
        }

        return new VideoItem($video);

    }

    /**
     * Display the specified resource.
     *
     * @param Video $video
     * @return VideoItem
     */
    public function show(Video $video)
    {
        return new VideoItem($video);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Video $video
     * @return VideoItem
     */
    public function update(Request $request, Video $video)
    {
        // updating title
        if($request->get('title')){
            $video->title = $request->get('title');

            if(!$request->get('slug')){
                $video->slug = Str::slug($request->get('title'));
            }
        }

        // updating slug
        if($request->get('slug')){
            $video->slug = Str::slug($request->get('slug'));
        }


        if($request->get('youtube_link')){
            $video->youtube_link = $request->get('youtube_link');

            $video->upload_method = Video::UPLOAD_METHOD_YOUTUBE;

        }else if($request->file('video')){ // updating video file
            $videoFile = Storage::disk('videos')->put('/', $request->file('video'));

            $video->file_path = $videoFile;

            $video->upload_method = Video::UPLOAD_METHOD_DIRECT;
        }

        if($request->file('thumbnail')){
            $thumbnailFile = Storage::disk('thumbnail')->put("/{$video->id}/", $request->file('thumbnail'));

            $video->thumbnail = $thumbnailFile;
        }

        $video->save();

        $video->save();

        // updating categories
        if($request->get('categories')){
            $video->categories()->sync(Category::whereIn('id', $request->get('categories'))->get());
        }

        return new VideoItem($video);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Video $video
     * @return VideoItem
     * @throws \Exception
     */
    public function destroy(Video $video)
    {
        $video->delete();

        return new VideoItem($video);
    }

    /**
     * Add comment to a video
     *
     * @param VideoComment $request
     * @param Video $video
     * @return void
     */
    public function comment(VideoComment $request, Video $video){

        $user = Auth::user();

        $comment = new Comment();
        $comment->text = $request->get('text');
        $comment->user_id = $user->id;
        $comment->video()->associate($video);
        $comment->save();
    }
}
