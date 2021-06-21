<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChannelImportRequest;
use App\Http\Requests\ChannelStore;
use App\Http\Requests\ChannelUpdate;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Channel\ImportRequestsCollection;
use App\Http\Resources\ChannelItem;
use App\Http\Resources\ChannelSummaryCollection;
use App\Http\Resources\VideoCollection;
use App\Mail\ImportRequestCompletedMail;
use App\Models\Channel;
use App\Models\UserVideo;
use App\Models\Video;
use App\Notifications\ImportRequestAccepted;
use App\Notifications\ImportRequestCompleted;
use App\Notifications\UpdateChannelStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ChannelSummaryCollection
     */
    public function index(Request $request)
    {
        if($request->is('api/admin/channels')){
            $query = Channel::query();
        }else{
            $query = Channel::published();
        }

        $filters = $request->get('filters', []);

        $searchFilter = Arr::get($filters, 'search');

        if($searchFilter){
            $query->where(function ($query) use ($searchFilter) {
                $query->SearchByOwner($searchFilter);
            })->orWhere(function ($query) use($searchFilter) {
                $query->SearchTitle($searchFilter);
            });
        }

        $sort = $request->get('sort');
        if($sort === 'most_uploads'){
            $query->withCount('videos')->orderBy('videos_count', 'desc');
        }elseif ($sort === 'most_subscribers'){
            $query->withCount('subscribers')->orderBy('subscribers_count', 'desc');
        }elseif ($sort === 'most_points'){
            $query->orderBy('points', 'desc');
        }

        $channels = $query->paginate();

        return new ChannelSummaryCollection($channels);

    }

    public function topChannels()
    {
        $video_ids = UserVideo::whereDate('created_at', '>', Carbon::now()->subDays(30));
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

        if($request->is('api/admin/channels')){
            $channel->user_id = $request->get('user_id');
        }else{
            $channel->user_id = auth('guard')->id();
        }

        $channel->slogan = $request->get('slogan', $channel->slogan);

        $channel->website = $request->get('website', $channel->website);
        $channel->instagram = $request->get('instagram', $channel->instagram);
        $channel->facebook = $request->get('facebook', $channel->facebook);
        $channel->twitter = $request->get('twitter', $channel->twitter);


        if($request->is('api/admin/channels') && $request->get('status')){
            $channel->status = array_flip(Channel::STATUS_TEXT)[$request->get('status')];
        }


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

            $channel = Channel::where('id', $id_or_slug)->orWhere('slug', $id_or_slug)->firstOrFail();

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

        if(!$request->is('api/admin/channels/*') && $channel->owner->id != auth('api')->id()){
            return new ChannelItem($channel);
        }

        $prev_status = $channel->status;

        $channel->name = $request->get('name', $channel->name);
        $channel->description = $request->get('description', $channel->description);

        $channel->slug = $request->get('slug')? $request->get('slug'): Str::slug($request->get('name', $channel->name));

        $channel->cover = $request->get('cover', $channel->cover);
        $channel->avatar = $request->get('avatar', $channel->avatar);

        if($request->get('intro_video_id')){
            $channel->intro_video_id = $request->get('intro_video_id');
        }

        $channel->slogan = $request->get('slogan', $channel->slogan);

        $channel->website = $request->get('website', $channel->website);
        $channel->instagram = $request->get('instagram', $channel->instagram);
        $channel->facebook = $request->get('facebook', $channel->facebook);
        $channel->twitter = $request->get('twitter', $channel->twitter);


        if($request->is('api/admin/channels/*') && $request->get('status')){
            $channel->status = array_flip(Channel::STATUS_TEXT)[$request->get('status')];
            $current_status = $channel->status;
        }

        if($request->is('api/admin/channels/*') && $request->get('points')){
            $channel->points = $request->get('points');
        }

        $channel->save();


        if($request->is('api/admin/*') && $prev_status != $current_status){
            $channel->owner->notify(new UpdateChannelStatus('publisher', [
                'prev_status' => Channel::STATUS_TEXT[$prev_status],
                'current_status' => Channel::STATUS_TEXT[$current_status],
            ]));
        }

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

    public function importRequest(ChannelImportRequest $request, Channel $channel){

        $channel->import_request_status = Channel::IMPORT_STATUS_REQUESTED;

        $channel->youtube_channel_id = $request->get("youtube_channel_id");

        $channel->save();

        $user = $channel->owner;

        $user->notify(new ImportRequestAccepted('publisher',
            [
                'channel' => ChannelMinimalItem::make($channel)
            ]
        ));

        return response()->json([
            'message' => __('channel.messages.import_request_submitted'),
        ]);
    }

    public function importRequests(){
        $requests = Channel::where('import_request_status', Channel::IMPORT_STATUS_REQUESTED)->get();

        return ImportRequestsCollection::make($requests);
    }

    public function importCompleted(Channel $channel){

        $channel->import_request_status = Channel::IMPORT_STATUS_COMPLETED;
        $channel->save();

        $user = $channel->owner;

        $user->notify(new ImportRequestCompleted('publisher',
            [
                'channel' => ChannelMinimalItem::make($channel)
            ]
        ));

        Mail::to($user->email)
            ->queue(new ImportRequestCompletedMail());

        return response()->json([
            'message' => __('channel.messages.import_completed'),
        ]);
    }


}
