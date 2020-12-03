<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaylistCollection;
use App\Http\Resources\PlaylistItem;
use App\Models\Playlist;
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

        $playlists = $query->paginate();

        return new PlaylistCollection($playlists);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return PlaylistItem
     */
    public function store(Request $request)
    {
        $playlist = new Playlist();

        $playlist->name = $request->get('name');

        // TODO:: maybe we could use better hash than MD5
        $playlist->url_hash = md5($request->get('name'));
        $playlist->user()->associate(Auth::user());
        $playlist->save();

        return new PlaylistItem($playlist);
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
     * @param Playlist $playlist
     * @return PlaylistItem
     */
    public function update(Request $request, Playlist $playlist)
    {
        $playlist->name = $request->get('name');

        // TODO:: maybe we could use better hash than MD5
        $playlist->url_hash = md5($request->get('name'));
        $playlist->user()->associate(Auth::user());
        $playlist->save();

        return new PlaylistItem($playlist);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Playlist $playlist
     * @return void
     */
    public function destroy(Playlist $playlist)
    {
        $playlist->delete();
    }
}
