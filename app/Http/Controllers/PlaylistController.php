<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaylistStore;
use App\Http\Requests\PlaylistUpdate;
use App\Http\Resources\Playlist\PlaylistResource;
use App\Models\Channel;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistController extends Controller
{
    public function index(Request $request, $channelIdOrSlug = null)
    {
        if($channelIdOrSlug){
            $channel = Channel::where('id', $channelIdOrSlug)->orWhere('slug', $channelIdOrSlug)->firstOrFail();
            $owner = $channel->owner()->firstOrFail();
            $query = Playlist::where('user_id', $owner->id);

            if (!$request->is('api/admin/*')){
                $query->where('status', Playlist::STATUS_PUBLIC);
            }

        }else{
            $query = Playlist::mine();
        }

        $playlists = $query->get();

        $playlists->append(['total_videos_count', 'published_videos_count']);

        return PlaylistResource::collection($playlists);
    }

    public function store(PlaylistStore $request)
    {
        $playlist = new Playlist();

        $playlist->name = $request->get('name');
        $playlist->status = array_flip(Playlist::STATUS_TEXT)[$request->get('status')]?? Playlist::STATUS_PUBLIC;

        if($request->is('api/admin/playlists')){
            $playlist->user_id = $request->get('user_id');
        }else{
            $playlist->owner()->associate(Auth::user());
        }

        $playlist->save();

        return PlaylistResource::make($playlist);
    }

    public function show($idOrHash)
    {
        $playlist = Playlist::public()->where('id', $idOrHash)->orWhere('url_hash', $idOrHash)->firstOrFail();
        $playlist->load(['channel'])->append(['total_videos_count', 'published_videos_count']);
        return PlaylistResource::make($playlist);
    }

    public function update(PlaylistUpdate $request, Playlist $playlist)
    {
        if (!request()->is('api/admin/*') && $playlist->user_id != auth('api')->id()){
            abort(403, 'Access denied');
        }

        $playlist->name = $request->get('name');
        $playlist->status = array_flip(Playlist::STATUS_TEXT)[$request->get('status')]?? Playlist::STATUS_PUBLIC;

        $playlist->owner()->associate(Auth::user());
        $playlist->save();

        return PlaylistResource::make($playlist);
    }

    public function destroy(Playlist $playlist)
    {
        if (!request()->is('api/admin/*') && $playlist->user_id != auth('api')->id()){
            abort(403, 'Access denied');
        }

        $playlist->videos()->detach();
        $playlist->delete();

        return response()->json(['message' => 'ok']);
    }

    public function add(Playlist $playlist, Video $video)
    {
        if($playlist->user_id != Auth::user()->id){
            abort(404, 'general.not_found');
        }

        $playlist->videos()->syncWithoutDetaching($video);

        return response()->json([
            'message' => 'general.successful'
        ]);
    }

    public function remove(Playlist $playlist, Video $video)
    {
        if($playlist->owner->id != Auth::user()->id){
            return response()->json([
                'message' => 'general.not_found'
            ], 404);
        }

        $playlist->videos()->detach($video);

        return response()->json([
            'message' => 'general.successful'
        ]);
    }

    public function bulkAdd(Request $request)
    {
        $playlists = Playlist::where('user_id', auth('api')->id())
            ->whereIn('id', $request->get('playlists',[]))
            ->get();
        $videos = Video::whereIn('id', $request->get('videos', []))->get();

        $playlists->map(function($playlist) use($videos) {
            $playlist->videos()->syncWithoutDetaching($videos);
        });

        return response()->json([
            'message' => 'general.successful'
        ]);
    }

    public function bulkRemove(Request $request)
    {
        $playlists = Playlist::whereIn('id', $request->get('playlists',[]))->get();
        $videos = Video::whereIn('id', $request->get('videos', []))->get();

        $playlists->map(function($playlist) use($videos) {
            $playlist->videos()->detach($videos);
        });

        return response()->json([
            'message' => 'general.successful'
        ]);
    }
}
