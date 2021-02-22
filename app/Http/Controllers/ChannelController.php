<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChannelStore;
use App\Http\Requests\ChannelUpdate;
use App\Http\Resources\ChannelItem;
use App\Http\Resources\ChannelSummaryCollection;
use App\Http\Resources\VideoCollection;
use App\Models\Channel;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ChannelSummaryCollection
     */
    public function index()
    {
        $query = Channel::published();

        $channels = $query->paginate();

        return new ChannelSummaryCollection($channels);

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

        $channel->cover = $request->get('cover');
        $channel->avatar = $request->get('avatar');

        if($request->get('intro_video_id')){
            $channel->intro_video_id = $request->get('intro_video_id');
        }

        $channel->user_id = Auth::user()->id;

        $channel->save();

        return new ChannelItem($channel);

    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param null $id_or_slug
     * @return ChannelItem
     */
    public function show(Request $request, $id_or_slug=null)
    {
        if($id_or_slug){

            $channel = Channel::where('id', $id_or_slug)->orWhere('slug', $id_or_slug)->first();

        }else{

            $user = Auth::guard('api')->user();
            $userChannel = $user->channel;

            if(is_null($userChannel)){

                $newChannel = new Channel();
                $newChannel->name = $user->username ? $user->username : $user->email;
                $newChannel->owner()->associate($user);
                $newChannel->save();
                $channel = $newChannel;

            }else{

                $channel = $userChannel;

            }

        }

        $result = new ChannelItem($channel);

        if(in_array('videos', explode(',', $request->get('include', '')))){

            $videos = Video::published()->whereHas('channels', function ($query) use ($id_or_slug) {
                return $query->where('id', $id_or_slug)->orWhere('slug', $id_or_slug);
            })->paginate()->appends($request->all());

            $result->additional([
                'videos' => VideoCollection::make($videos)
            ]);
        }

        return $result;


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
        if(is_null($request->route('channel'))){
            $channel = Auth::user()->channel;
        }

        $channel->name = $request->get('name');
        $channel->description = $request->get('description');

        $channel->slug = $request->get('slug')? $request->get('slug'): Str::slug($request->get('name'));

        $channel->cover = $request->get('cover');
        $channel->avatar = $request->get('avatar');

        if($request->get('intro_video_id')){
            $channel->intro_video_id = $request->get('intro_video_id');
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

        if($channel->owner->id != Auth::user()->id){
            throw new NotFoundHttpException();
        }

        if(is_null($video->channels()->first())){
            $channel->videos()->attach($video);
        }
    }

    /**
     * @param Channel $channel
     * @param Video $video
     */
    public function remove(Channel $channel, Video $video){

        if($channel->owner->id != Auth::user()->id){
            throw new NotFoundHttpException();
        }

        $channel->videos()->detach($video);
    }

    /**
     * @param Channel $channel
     */
    public function subscription(Channel $channel){

        $user = Auth::user();

        if($channel->subscribers()->find($user->id)){
            $channel->subscribers()->detach(Auth::user());
        }else{
            $channel->subscribers()->attach(Auth::user());
        }

        return new ChannelItem($channel);

    }


}
