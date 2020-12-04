<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChannelStore;
use App\Http\Requests\ChannelUpdate;
use App\Http\Resources\ChannelCollection;
use App\Http\Resources\ChannelItem;
use App\Models\Channel;
use App\Models\Video;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ChannelCollection
     */
    public function index()
    {
        $query = Channel::published();

        $channels = $query->paginate();

        return new ChannelCollection($channels);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return ChannelItem
     */
    public function store(ChannelStore $request)
    {
        $channel = new Channel();

        $channel->name = $request->get('name');
        $channel->description = $request->get('description');
        $channel->slug = Str::slug($request->get('name'));

        if($request->file('cover')){
            $coverPhoto = Storage::disk('channels')->put('/', $request->file('cover'));
            $channel->cover = $coverPhoto;
        }

        if($request->file('image')){
            $channelImage = Storage::disk('channels')->put('/', $request->file('image'));
            $channel->image = $channelImage;
        }

        if($request->get('intro_video_id')){
            $channel->intro_video_id = $request->get(intro_video_id);
        }

        $channel->user_id = Auth::user()->id;

        $channel->save();

        return new ChannelItem($channel);

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
     * @param Channel $channel
     * @return ChannelItem
     */
    public function update(ChannelUpdate $request, Channel $channel)
    {

        $channel->name = $request->get('name');
        $channel->description = $request->get('description');

        $channel->slug = $request->get('slug')? $request->get('slug'): Str::slug($request->get('name'));

        if($request->file('cover')){
            // Delete old cover file
            $coverPhoto = Storage::disk('channels')->put('/', $request->file('cover'));
            $channel->cover = $coverPhoto;
        }

        if($request->file('image')){
            // Delete old image file
            $channelImage = Storage::disk('channels')->put('/', $request->file('image'));
            $channel->image = $channelImage;
        }

        if($request->get('intro_video_id')){
            $channel->intro_video_id = $request->get(intro_video_id);
        }

        $channel->save();

        return new ChannelItem($channel);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Channel $channel
     * @return void
     */
    public function destroy(Channel $channel)
    {
        $channel->delete();
    }

    /**
     * @param Channel $channel
     * @param Video $video
     */
    public function add(Channel $channel, Video $video){
        $channel->videos()->attach($video);
    }

    /**
     * @param Channel $channel
     * @param Video $video
     */
    public function remove(Channel $channel, Video $video){
        $channel->videos()->detach($video);
    }
}
