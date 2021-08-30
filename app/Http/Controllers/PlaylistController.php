<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaylistStore;
use App\Http\Requests\PlaylistUpdate;
use App\Http\Resources\PlaylistCollection;
use App\Http\Resources\PlaylistItem;
use App\Models\Channel;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use Exception;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return PlaylistCollection
     */
    public function index(Request $request, $channelIdOrSlug = null)
    {
        if($channelIdOrSlug){
            $channel = Channel::where('id', $channelIdOrSlug)->orWhere('slug', $channelIdOrSlug)->firstOrFail();
            $query = Playlist::where('user_id', $channel->owner->id);

            if (!$request->is('api/admin/*')){
                $query->where('status', Playlist::STATUS_PUBLIC);
            }

        }else{
            $query = Playlist::mine();
        }

        $playlists = $query->get();

        return new PlaylistCollection($playlists);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param PlaylistStore $request
     * @return PlaylistItem
     */
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

        return new PlaylistItem($playlist);
    }

    /**
     * Display the specified resource.
     *
     * @param Playlist $playlist
     * @return PlaylistItem
     */
    public function show($idOrHash)
    {
        $playlist = Playlist::public()->where('id', $idOrHash)->orWhere('url_hash', $idOrHash)->firstOrFail();
        return new PlaylistItem($playlist);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param PlaylistUpdate $request
     * @param Playlist $playlist
     * @return PlaylistItem
     */
    public function update(PlaylistUpdate $request, Playlist $playlist)
    {
        if (!request()->is('api/admin/*') && $playlist->user_id != auth('api')->id()){
            abort(403, 'Access denied');
        }

        $playlist->name = $request->get('name');
        $playlist->status = array_flip(Playlist::STATUS_TEXT)[$request->get('status')]?? Playlist::STATUS_PUBLIC;

        $playlist->owner()->associate(Auth::user());
        $playlist->save();

        return new PlaylistItem($playlist);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Playlist $playlist
     * @return void
     * @throws Exception
     */
    public function destroy(Playlist $playlist)
    {
        if (!request()->is('api/admin/*') && $playlist->user_id != auth('api')->id()){
            abort(403, 'Access denied');
        }

        $playlist->videos()->detach();
        $playlist->delete();

        return response()->json(['message' => 'ok']);
    }

    /**
     * @param Playlist $playlist
     * @param Video $video
     */
    public function add(Playlist $playlist, Video $video){

        if($playlist->user_id != Auth::user()->id){
            abort(404, 'general.not_found');
        }

        $playlist->videos()->syncWithoutDetaching($video);

        return response()->json([
            'message' => 'general.successful'
        ]);
    }

    /**
     * @param Playlist $playlist
     * @param Video $video
     */
    public function remove(Playlist $playlist, Video $video){

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

    public function bulkAdd(Request $request){
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

    public function bulkRemove(Request $request){
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
