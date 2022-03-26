<?php

namespace App\Http\Controllers;

use App\Events\VideoCommented;
use App\Events\VideoCreated;
use App\Events\VideoDeleted;
use App\Events\VideoUpdated;
use App\Events\VideoViewed;
use App\Events\VideoWasHidden;
use App\Events\VideoWasUnHidden;
use App\Events\VideoWatched;
use App\Http\Requests\VideoComment;
use App\Http\Requests\VideoStore;
use App\Http\Requests\VideoUpdate;
use App\Http\Requests\WatchTimeStore;
use App\Http\Resources\CryptoCurrency\CryptoCurrencyResource;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Http\Resources\VideoCollection;
use App\Http\Resources\VideoSummaryItem;
use App\Models\Category;
use App\Models\Comment;
use App\Models\CryptoCurrency;
use App\Models\Option;
use App\Models\Playlist;
use App\Models\Tag;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $perPage = $request->get('per_page') ?: 15;
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
        $cryptoCurrencyId = Arr::get($filters, 'cryptocurrency_id');
        $cryptoCurrencySlug = Arr::get($filters, 'cryptocurrency_slug');
        $playlistId = Arr::get($filters, 'playlist');
        $playlistHash = Arr::get($filters, 'playlist_hash');
        $channelId = Arr::get($filters, 'channel');
        $channelSlug = Arr::get($filters, 'channel_slug');

        if($categorySlug){
            $category = Category::where('slug', $categorySlug)->firstOrFail();
            $categoryId = $category->id;
        }

        if($playlistHash){
            $playlist = Playlist::where('url_hash', $playlistHash)->firstOrFail();
            $playlistId = $playlist->id;
        }

        if($cryptoCurrencySlug){
            $cryptoCurrency = CryptoCurrency::where('slug', $cryptoCurrencySlug)->firstOrFail();
            $cryptoCurrencyId = $cryptoCurrency->id;
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

        if(!empty($cryptoCurrencyId)){
            $query->filterCryptoCurrency($cryptoCurrencyId);
        }

        if($playlistId){
            $query->inPlaylist($playlistId);
        }

        $sort = $request->get('sort');
        if($sort === 'most_liked'){
            $query->withCount(['likedBy', 'dislikedBy'])->orderByRaw('(liked_by_count - disliked_by_count) DESC');
        }elseif ($sort === 'most_viewed'){
            $query->orderBy('view_count', 'desc');
        }elseif ($sort === 'published_at'){
            $query->orderBy('published_at', 'desc');
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

        if($channelSlug){
            $query->whereHas('channel', function($q) use ($channelSlug){
                return $q->where('slug', $channelSlug);
            });
        }

        $videos = $query->paginate($perPage);

        $result = \App\Http\Resources\Video\VideoItem::collection($videos);

        if($categorySlug){
            $result->additional([
                'category' => $categorySlug == "all" ? "All" : $category->name
            ]);
        }

        if($cryptoCurrencySlug){
            $result->additional([
                'cryptocurrency' => CryptoCurrencyResource::make($cryptoCurrency)
            ]);
        }

        return $result;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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

        }elseif($request->file('video')){ // adding file to video

            $videoFile = Storage::disk('videos')->put('/', $request->file('video'));
            $video->file_path = $videoFile;

        }elseif($request->get('file_url')){ // adding file to video

            $video->file_url = $request->get('file_url');
        }

        // adding user to video
        if($request->is('api/admin/videos')){
            $video->user_id = $request->get('user_id');
        }else{
            $video->user_id = auth('api')->user()->id;
        }

        // thumbnail
        $video->thumbnail_url = $request->get('thumbnail');

        // status
        if($request->get('status')){
            $video->status = array_flip(Video::STATUS_TEXT)[$request->get('status')];
        }

        // duration
        if($request->get('duration')){
            $video->duration = $request->get('duration');
        }

        // adding main category
        if($request->get('category')){
            $video->category()->associate($request->get('category'));
        }

        if($request->get('language')){
            $video->language_id = $request->get('language');
        }


        DB::transaction(function () use ($request, $video){

            $video->save();

            // adding categories
            if($request->get('categories')){
                $video->categories()->saveMany(Category::whereIn('id', $request->get('categories'))->get());
            }

            // adding crypto currencies
            if($request->get('crypto_currencies')){
                $video->crypto_currencies()->sync($request->get('crypto_currencies'));
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
        });

        event(new VideoCreated($video));

        return new \App\Http\Resources\Video\VideoItem($video);
    }

    /**
     * Display the specified resource.
     *
     * @param mixed $id_or_url_hash
     * @param Request $request
     */
    public function show($id_or_url_hash, Request $request)
    {
        $video = Video::where('id', $id_or_url_hash)->orWhere('url_hash', $id_or_url_hash)->firstorFail();

        $isAdmin = $request->is('api/admin/*');

        if($isAdmin || $video->isPublished || $video->isMine){
            return new \App\Http\Resources\Video\VideoItem($video);
        }

        return response()->json([
            'message' => 'You can\'t access this video'
        ], 422);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Video $video
     */
    public function update(VideoUpdate $request, Video $video)
    {
        $oldVideo = clone $video;

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

        // thumbnail
        $video->thumbnail_url = $request->get('thumbnail');

        // status
        if($request->get('status') && $oldVideo->status != Video::STATUS_HIDDEN){
            $video->status = array_flip(Video::STATUS_TEXT)[$request->get('status')];
        }

        // adding main category
        if($request->get('category')){
            $video->category()->associate($request->get('category'));
        }

        if($request->get('language')){
            $video->language_id = $request->get('language');
        }else{
            $video->language_id = null;
        }

        $video->save();


        DB::transaction(function () use ($request, $video){

            $video->save();

            // updating categories
            if($request->get('categories')){
                if(is_array($request->get('categories'))){
                    $video->categories()->sync(Category::whereIn('id', $request->get('categories'))->get());
                }else{
                    $video->categories()->sync(Category::where('id', $request->get('categories'))->get());
                }
            }

            // updating crypto currency
            if($request->get('crypto_currencies')){
                $video->crypto_currencies()->sync($request->get('crypto_currencies'));
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
            }else{
                $video->tags()->sync([]);
            }

            // adding playlist
            if($request->get('playlists')){
                $video->playlists()->sync(Playlist::whereIn('id', $request->get('playlists'))->get());
            }
        });

        event(new VideoUpdated($oldVideo, $video));

        return new \App\Http\Resources\Video\VideoItem($video);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Video $video
     * @return \Illuminate\Http\JsonResponse | VideoSummaryItem
     * @throws \Exception
     */
    public function destroy(Request $request, Video $video)
    {
        if(!(request()->is('api/admin/videos/*') || $video->user->id === Auth::guard('api')->id())){
            return response()->json([
                'general.not_authorized'
            ], 403);
        }

        if(request()->is('api/admin/videos/*')){
            $request->validate([
                'reason' => 'required'
            ]);

            $option_key = Option::VIDEO_DELETE_REASONS;
            $reasons = json_decode(Option::where("key", $option_key)->first()->value?? abort(404));


            if(($key = array_search($request->get('reason'), array_column($reasons, 'key'))) !== false ){
                $video->reason_key = $request->get('reason');
                $video->reason_text = $reasons[$key]->value;
            }else{
                $video->reason_key = 'other';
                $video->reason_text = $request->get('reason');
            }

            $video->save();
        }

        $video->delete();

        event(new VideoDeleted($video));

        return new VideoSummaryItem($video);
    }

    public function bookmarks()
    {
        $perPage = request()->get('per_page') ?: 15;

        return \App\Http\Resources\Video\VideoItem::collection(auth('api')->user()->bookmarkVideos()->paginate($perPage));
    }

    /**
     * Add comment to a video
     *
     * @param VideoComment $request
     * @param Video $video
     * @return void
     */
    public function storeComment(VideoComment $request, $videoIdOrHash)
    {
        $video = Video::published()->where('id', $videoIdOrHash)->orWhere('url_hash', $videoIdOrHash)->firstOrFail();

        $user = Auth::user();

        $comment = new Comment();
        $comment->text = $request->get('text');
        $comment->user_id = $user->id;
        $comment->video()->associate($video);
        $comment->save();

        event(new VideoCommented($video, auth('api')->user()));

        return new \App\Http\Resources\Comment\CommentItem($comment);
    }

    public function comments($id_or_url_hash)
    {
        $video = Video::published()->where('id', $id_or_url_hash)->orWhere('url_hash', $id_or_url_hash)->firstOrFail();

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

    public function hide(Request $request, Video $video){

        $request->validate([
            'reason' => 'required'
        ]);

        if ($video->status != Video::STATUS_PUBLISHED){
            return response()->json([
                'message' => __('video.video_is_not_published')
            ], 422);
        }

        $option_key = Option::VIDEO_HIDE_REASONS;
        $reasons = json_decode(Option::where("key", $option_key)->first()->value) ?? abort(404);


        if(($key = array_search($request->get('reason'), array_column($reasons, 'key'))) !== false ){
            $video->reason_key = $request->get('reason');
            $video->reason_text = $reasons[$key]->value;
        }else{
            $video->reason_key = 'other';
            $video->reason_text = $request->get('reason');
        }


        $video->status = Video::STATUS_HIDDEN;
        $video->save();

        event(new VideoWasHidden($video));

        return VideoMinimalItem::make($video);
    }

    public function unHide(Video $video)
    {
        $video->reason_key = null;
        $video->reason_text = null;
        $video->status = Video::STATUS_PUBLISHED;
        $video->save();

        event(new VideoWasUnHidden($video));

        return VideoMinimalItem::make($video);
    }

    public function related_videos($id_or_url_hash)
    {
        $video = Video::published()->where('id', $id_or_url_hash)->orWhere('url_hash', $id_or_url_hash)->first();

        abort_if(is_null($video), 404);


        $query = Video::published()->where("id", "!=", $video->id);

        $tags = $video->tags()->pluck('id')->toArray();
        $category = $video->category ? $video->category->id : null;

        if(!$category && !count($tags)){
            return \App\Http\Resources\Video\VideoItem::collection(new Paginator([],15));
        }

        $query->where(function ($query) use ($tags, $category){

            if($category){
                $query->whereHas('category', function($q) use ($category){
                    $q->where('id', $category);
                });
            }

            if( count($tags) ){
                $query->orWhereHas('tags', function($q) use ($tags){
                    $q->whereIn('id', $tags);
                });
            }
        });

        return \App\Http\Resources\Video\VideoItem::collection($query->paginate());
    }

    public function increase_view(Video $video)
    {
        $video->view_count++;
        $video->save();

        event(new VideoViewed($video, auth('api')->user()));

        return $video->view_count;
    }

    public function watch_time_store(WatchTimeStore $request, $idOrUrlHash)
    {
        $video = Video::published()->where('id', $idOrUrlHash)->orWhere('url_hash', $idOrUrlHash)->firstOrFail();
        $user = auth("api")->user();
        $originalStart = $start = $request->get("start_time");
        $originalEnd = $end = $request->get("end_time");
        $duration = 0;

        $watchTimes = DB::table('watch_times')
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where(function($query) use ($originalStart, $originalEnd) {
                $query->whereBetween('start_time', [$originalStart, $originalEnd])
                    ->orWhereBetween('end_time', [$originalStart, $originalEnd])
                    ->orWhere(function($query) use ($originalStart, $originalEnd) {
                        $query->where('start_time', '<=', $originalStart)
                            ->where('end_time', '>=', $originalEnd);
                    });
            })
            ->orderBy('start_time')
            ->get();


        // Calc new rows
        $newRows = [];
        foreach ($watchTimes as $watchTime){

            $end = $watchTime->start_time;

            if ($end <= $start){
                $start = $watchTime->end_time;
                continue;
            }

            $newRows[] = [
                "start_time" => $start,
                "end_time" => $end
            ];

            $duration += ($end - $start);

            $start = $watchTime->end_time;
        }

        if ($originalEnd - $start > 0){
            $newRows[] = [
                "start_time" => $start,
                "end_time" => $originalEnd
            ];

            $duration += ($originalEnd - $start);
        }

        // Add to Database
        DB::transaction(function () use ($video, $user, $newRows) {
            foreach ($newRows as $row){
                $video->watch_times()->attach($user->id, [
                    "start_time" => $row['start_time'],
                    "end_time" => $row['end_time']
                ]);
            }
        });

        $video->watch_time += $duration;
        $video->save();

        $user->watch_time += $duration;
        $user->save();

        foreach ($newRows as $row){
            event(new VideoWatched($video, $user, $row['start_time'], $row['end_time']));
        }

        return response()->json(["message" => "ok"]);
    }

}
