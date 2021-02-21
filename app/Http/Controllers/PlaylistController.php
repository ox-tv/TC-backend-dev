<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaylistStore;
use App\Http\Requests\PlaylistUpdate;
use App\Http\Resources\PlaylistCollection;
use App\Http\Resources\PlaylistItem;
use App\Models\Playlist;
use App\Models\Video;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return PlaylistCollection
     */
    public function index()
    {
        $query = Playlist::mine();

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

        $playlist->owner()->associate(Auth::user());
        $playlist->save();

        return new PlaylistItem($playlist);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show($id)
    {
        //
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
        $playlist->name = $request->get('name');

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
        $playlist->delete();
    }

    /**
     * @param Playlist $playlist
     * @param Video $video
     */
    public function add(Playlist $playlist, Video $video){

        if($playlist->owner->id != Auth::user()->id){
            return response()->json([
               'message' => 'general.not_found'
            ], 404);
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
        $playlists = Playlist::whereIn('id', $request->get('playlists',[]))->get();
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
