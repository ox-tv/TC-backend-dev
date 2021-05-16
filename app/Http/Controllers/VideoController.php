<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoComment;
use App\Http\Requests\VideoStore;
use App\Http\Requests\VideoUpdate;
use App\Http\Requests\WatchTimeStore;
use App\Http\Resources\CommentItem;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoItem;
use App\Http\Resources\VideoSummaryCollection;
use App\Http\Resources\VideoSummaryItem;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Playlist;
use App\Models\Tag;
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
        $adminVideos = $request->is('api/admin/videos');

        if($publisherVideos){
            $query = Video::mine();
        }else if($adminVideos){
            $query = Video::query();
        }else{
            $query = Video::published();
        }

        $filters = $request->get('filters', []);

        $timeFilter = Arr::get($filters, 'time');
        $searchFilter = Arr::get($filters, 'search');
        $categoryId = Arr::get($filters, 'category_id');
        $categorySlug = Arr::get($filters, 'category_slug');
        $playlistId = Arr::get($filters, 'playlist');
        $channelId = Arr::get($filters, 'channel');

        if($categorySlug){
            $category = Category::where('slug', $categorySlug)->first();
            $categoryId = is_null($category) ? null : $category->id;
        }

        if($timeFilter == 'week'){
            $query->week();
        }

        if($searchFilter){
            $query->where(function ($query) use ($searchFilter){
                $query->where(function ($query) use ($searchFilter){
                    $query->SearchTitle($searchFilter);
                })->orWhere(function ($query) use ($searchFilter){
                    $query->SearchDescription($searchFilter);
                });
            });
        }

        if($categoryId){
            $query->filterCategory($categoryId);
        }

        if($playlistId){
            $query->inPlaylist($playlistId);
        }

        $sort = $request->get('sort');
        if($sort === 'most_liked'){
            $query->withCount(['likedBy', 'dislikedBy'])->orderByRaw('(liked_by_count - disliked_by_count) DESC');
        }elseif ($sort === 'most_viewed'){
            $query->orderBy('views_count', 'desc');
        }elseif ($sort === 'most_commented'){
            $query->withCount('comments')->orderBy('comments_count', 'desc');
        }

        $excludedVideos = [];

        $excludePlaylistsId = $request->get('exclude_playlist');
        if($excludePlaylistsId){
            $playlistVideos = Playlist::find($excludePlaylistsId)->videos()->select('id')->get()->pluck('id')->toArray();
            $excludedVideos = array_merge($excludedVideos, $playlistVideos);
        }

        $excludeVideosIds = $request->get('exclude_videos', []);
        if(count($excludeVideosIds)>0){
            $excludedVideos = array_merge($excludedVideos, $excludeVideosIds);
        }

        if(count($excludedVideos) > 0){
            $query->whereNotIn('id', $excludedVideos);
        }

        if($channelId){
            $query->inChannel($channelId);
        }

        $videos = $query->paginate();

        $result = \App\Http\Resources\Video\VideoItem::collection($videos);

        if($categorySlug){
            $result->additional([
                'category' => $categorySlug == "all" ? "All" : $category->name
            ]);
        }

        return $result;
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
        $video->published_at = $request->get('published_at');

        if($request->is('api/admin/videos')){

            $video->file_path = $request->get('video_name');

        }elseif($request->get('youtube_link')){

            $video->youtube_link = $request->get('youtube_link');
            $video->upload_method = Video::UPLOAD_METHOD_YOUTUBE;

        }else if($request->file('video')){ // adding file to video

            $videoFile = Storage::disk('videos')->put('/', $request->file('video'));
            $video->file_path = $videoFile;
        }

        // adding user to video
        if($request->is('api/admin/videos')){
            $video->user_id = $request->get('user_id');
        }else{
            $video->user_id = auth()->user()->id;
        }

        // thumbnail
        $video->thumbnail = $request->get('thumbnail');

        // status
        if($request->get('status')){
            $video->status = array_flip(Video::STATUS_TEXT)[$request->get('status')];
        }

        // adding main category
        if($request->get('category')){
            $video->category()->associate($request->get('category'));
        }

        $video->save();

        // adding categories
        if($request->get('categories')){
            $video->categories()->saveMany(Category::whereIn('id', $request->get('categories'))->get());
        }

        // adding tags
        if($request->get('tags')){
            $tags = collect($request->get('tags', []));

            $tags->map(function ($tag) use ($video){
                $video->tags()->save(Tag::firstOrCreate([
                    'name' => $tag
                ]));
            });
        }

        // adding playlist
        if($request->get('playlists')){
            $video->playlists()->saveMany(Playlist::whereIn('id', $request->get('playlists'))->get());
        }

        return new VideoItem($video);

    }

    /**
     * Display the specified resource.
     *
     * @param mixed $id_or_url_hash
     * @param Request $request
     * @return VideoItem|\Illuminate\Http\JsonResponse
     */
    public function show($id_or_url_hash, Request $request)
    {
        $video = Video::where('id', $id_or_url_hash)->orWhere('url_hash', $id_or_url_hash)->first();

        if(is_null($video)){
            return response()->json([
                'message' => _('general.not_found')
            ],404);
        }

        if(!($video->isPublished || $video->isMine)){
            return response()->json([
                'message' => 'You can\'t access this video'
            ], 422);
        }

        return new \App\Http\Resources\Video\VideoItem($video);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Video $video
     * @return VideoItem
     */
    public function update(VideoUpdate $request, Video $video)
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

        $video->description = $request->get('description');


        if($request->get('youtube_link')){
            $video->youtube_link = $request->get('youtube_link');

            $video->upload_method = Video::UPLOAD_METHOD_YOUTUBE;

        }else if($request->file('video')){ // updating video file
            $videoFile = Storage::disk('videos')->put('/', $request->file('video'));

            $video->file_path = $videoFile;

            $video->upload_method = Video::UPLOAD_METHOD_DIRECT;
        }

        // thumbnail
        $video->thumbnail = $request->get('thumbnail');

        // status
        if($request->get('status')){
            $video->status = array_flip(Video::STATUS_TEXT)[$request->get('status')];
        }

        // adding main category
        if($request->get('category')){
            $video->category()->associate($request->get('category'));
        }

        $video->save();

        // updating categories
        if($request->get('categories')){
            if(is_array($request->get('categories'))){
                $video->categories()->sync(Category::whereIn('id', $request->get('categories'))->get());
            }else{
                $video->categories()->sync(Category::where('id', $request->get('categories'))->get());
            }
        }

        // updating tags
        if($request->get('tags')){
            $tags = collect($request->get('tags', []));

            $tagIds = $tags->map(function ($tag){
                return Tag::firstOrCreate([
                    'name' => $tag
                ])->id;
            });

            $video->tags()->sync(Tag::whereIn('id', $tagIds)->get());
        }

        // adding playlist
        if($request->get('playlists')){
            $video->playlists()->sync(Playlist::whereIn('id', $request->get('playlists'))->get());
        }

        return new VideoItem($video);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Video $video
     * @return \Illuminate\Http\JsonResponse | VideoSummaryItem
     * @throws \Exception
     */
    public function destroy(Video $video)
    {
        if(\request()->is('api/admin/videos/*')){
            $video->delete();
            return new VideoSummaryItem($video);
        }

        if($video->user->id === Auth::guard('api')->id()){
            $video->delete();
            return new VideoSummaryItem($video);
        }else{
            return response()->json([
                'general.not_authorized'
            ], 403);
        }
    }

    public function bookmarks()
    {
        return \App\Http\Resources\Video\VideoItem::collection(auth('api')->user()->bookmarkVideos);
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

        return new \App\Http\Resources\Comment\CommentItem($comment);
    }

    public function comments($id)
    {
        $video = Video::published()->findOrFail($id);

        return \App\Http\Resources\Comment\CommentItem::collection($video->comments()->with(["user", "replies"])->paginate());
    }

    public function bulkDestroy(Request $request){
        $request->validate([
            'videos.*' => 'exists:videos,id'
        ]);

        $videos = Video::whereIn('id', $request->get('videos'));


        $owners = $videos->select('user_id')->get()->pluck('user_id')->unique()->toArray();

        if(count($owners) == 1 && in_array(Auth::guard('api')->id(), $owners)){

            $videos->delete();

            return response()->json([
                'message' => 'general.successful'
            ]);

        }

        return response()->json([
            'message' => 'general.not_authorized'
        ], 403);

    }

    public function bulkPinMessage(Request $request){
        $request->validate([
            'videos.*' => 'exists:videos,id',
            'text' => 'required'
        ]);

        $userId = Auth::guard('api')->id();
        $videoIds = collect($request->get('videos'));
        $text = $request->get('text');

        $videoIds->map(function ($videoId) use ($userId, $text){
            $comment = new Comment();
            $comment->text = $text;
            $comment->user_id = $userId;
            $comment->video()->associate($videoId);
            $comment->is_pinned = Comment::COMMENT_PINNED;
            $comment->save();
        });

        return response()->json([
            'message' => 'general.successful'
        ]);

    }

    public function hide(Video $video){

        $video->status = Video::STATUS_HIDDEN;
        $video->save();

        return VideoMinimalItem::make($video);
    }

    public function related_videos($id)
    {
        $video = Video::published()->findOrFail($id);

        return \App\Http\Resources\Video\VideoItem::collection($video->related_videos);
    }

    public function increase_view(Video $video)
    {
        $video->view_count++;
        $video->save();

        return $video->view_count;
    }

    public function watch_time_store(WatchTimeStore $request, $id)
    {
        $video = Video::findOrFail($id);
        $user = auth("api")->user();

        $video->watch_times()->attach($user->id, [
            "start_time" => $request->get("start_time"),
            "end_time" => $request->get("end_time")
        ]);

        $duration = $request->get("end_time") - $request->get("start_time");

        $video->watch_time += $duration;
        $video->save();

        $user->watch_time += $duration;
        $user->save();

        return response()->json(["message" => "ok"]);
    }

}
