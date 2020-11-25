<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoStore;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoItem;
use App\Models\Category;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return VideoCollection
     */
    public function index()
    {
        $videos = Video::published()->get();

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

        // adding file to video
        if($request->file('video')){
            $videoFile = Storage::disk('videos')->put('/', $request->file('video'));

            $video->file_path = $videoFile;
        }

        // adding user to video
        $video->user_id = auth()->user()->id;

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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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

        // updating video file
        if($request->file('video')){
            $videoFile = Storage::disk('videos')->put('/', $request->file('video'));

            $video->file_path = $videoFile;
        }

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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
