<?php

namespace App\Http\Controllers;

use App\Events\ChannelSubscribed;
use App\Exports\PublisherEarningsExport;
use App\Http\Requests\ChannelImportRequest;
use App\Http\Requests\ChannelStore;
use App\Http\Requests\ChannelUpdate;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Channel\ImportRequestsCollection;
use App\Http\Resources\ChannelItem;
use App\Http\Resources\ChannelSummaryCollection;
use App\Http\Resources\Video\VideoItem;
use App\Http\Resources\VideoCollection;
use App\Mail\ImportRequestCompletedMail;
use App\Models\Channel;
use App\Models\ChannelStatisticsDaily;
use App\Models\Earning;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserVideo;
use App\Models\Video;
use App\Models\VideoStatisticsDaily;
use App\Notifications\ImportRequestAccepted;
use App\Notifications\ImportRequestCompleted;
use App\Notifications\TCNotification\TCNotification;
use App\Notifications\UpdateChannelStatus;
use App\Services\PointService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
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
        $per_page = $request->get('per_page') ?: 15;

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
        }elseif ($sort === 'most_comments'){
            $query->withCount('comments')->orderBy('comments_count', 'desc');
        }

        $channels = $query->paginate($per_page);

        return new ChannelSummaryCollection($channels);

    }

    public function topChannels()
    {
        $datetime = (Carbon::now())->subDays(30);
        $channelIds = VideoStatisticsDaily::selectRaw('SUM(points) as points, channel_id')
            ->whereDate('date', '>=', $datetime->format('Y-m-d'))
            ->groupBy('channel_id')
            ->orderBy('points', 'DESC')
            ->withoutGlobalScope('orderByDate')
            ->pluck('channel_id')->toArray();

        $orderByIds = implode(',', array_reverse($channelIds));

        $channels = Channel::where('status', Channel::STATUS_PUBLISHED)
            ->orderByRaw(($orderByIds?"FIELD(id, $orderByIds) DESC, ":"") . "Created_at DESC")
            ->paginate();

        return \App\Http\Resources\Channel\ChannelItem::collection($channels);
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

            $videos = Video::published()->whereHas('channel', function ($query) use ($id_or_slug) {
                return $query->where('id', $id_or_slug)->orWhere('slug', $id_or_slug);
            })->paginate()->appends($request->all());

            $result->additional([
                'videos' => VideoItem::collection($videos)
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
            $channel = auth('api')->user()->channel;
        }

        if(!$request->is('api/admin/channels/*') && $channel->owner->id != auth('api')->id()){
            abort(403, 'You do not have permission to access');
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


        if($request->is('api/admin/*') && !empty($current_status) && $prev_status != $current_status){
            TCNotification::send(collect([$channel->owner]), new UpdateChannelStatus(
                Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
                Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                [
                    'prev_status' => Channel::STATUS_TEXT[$prev_status],
                    'current_status' => Channel::STATUS_TEXT[$current_status],
                ],
                get_class($channel),
                $channel->id
            ));
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
     * Removed from system
     */
    public function add(Channel $channel, Video $video){

        if($channel->owner->id != Auth::user()->id){
            throw new NotFoundHttpException();
        }

        if (empty($video->channel_id)){
            $video->channel_id = $channel->id;
            $video->save();
        }
    }

    /**
     * Removed from system
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

        $subscribedBefore = $channel->subscribers()->where('id', $user->id)->exists();

        if($subscribedBefore){
            $channel->subscribers()->detach(Auth::user());
        }else{
            $channel->subscribers()->attach(Auth::user());
        }

        event(new ChannelSubscribed(
            $channel,
            auth('api')->user(),
            $subscribedBefore?0:1,
            $subscribedBefore?1:0));

        return new \App\Http\Resources\Channel\ChannelItem($channel);
    }

    public function importRequest(ChannelImportRequest $request, Channel $channel){

        $channel->import_request_status = Channel::IMPORT_STATUS_REQUESTED;

        $channel->youtube_channel_id = $request->get("youtube_channel_id");

        $channel->save();

        TCNotification::send(collect([$channel->owner]), new ImportRequestAccepted(
            Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'channel' => ChannelMinimalItem::make($channel),
            ],
            get_class($channel),
            $channel->id
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

        TCNotification::send(collect([$channel->owner]), new ImportRequestCompleted(
            Notification::SCOPE_TEXT[Notification::SCOPE_PUBLISHER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'channel' => ChannelMinimalItem::make($channel)
            ],
            get_class($channel),
            $channel->id
        ));

        Mail::to($channel->owner->email)
            ->queue(new ImportRequestCompletedMail());

        return response()->json([
            'message' => __('channel.messages.import_completed'),
        ]);
    }

    public function performanceTotal(Request $request,PointService $pointService, User $user = null)
    {
        $pointPerHeroSub = config('general.points.per_subscribe_hero');
        $pointPerNonHeroSub = config('general.points.per_subscribe_non_hero');

        if (!$request->is('api/admin/*')){
            $user = auth('api')->user();
        }

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from');
        $to = Arr::get($filters, 'to');

        $earningAmount = Earning::where('user_id', $user->id)->when($from, function ($q, $from){
                $q->where('date', '>=', $from);
            })->when($to, function ($q, $to){
                $q->where('date', '<=', $to);
            })->sum('amount');

        // Calc total points
        $totalPoints = VideoStatisticsDaily::when($from, function ($q, $from){
                $q->where('date', '>=', $from);
            })->when($to, function ($q, $to){
                $q->where('date', '<=', $to);
            })->sum('points');

        $heroSubCounts = User::when($to, function ($q, $to){
            $q->whereHas('subscribedChannels', function ($q) use ($to){
                $q->where('channel_user.created_at', '<=', $to);
            });
        })->isHero()->count();

        $nonHeroSubCounts = User::when($to, function ($q, $to){
            $q->whereHas('subscribedChannels', function ($q) use ($to){
                $q->where('channel_user.created_at', '<=', $to);
            });
        })->isNonHero()->count();

        $totalPoints += ($heroSubCounts * $pointPerHeroSub);
        $totalPoints += ($nonHeroSubCounts * $pointPerNonHeroSub);

        $result = [
            //'points_hero' => $pointService->calcHeroPoint($user,['from' => $from, 'to' => $to]),
            //'points_non_hero' => $pointService->calcNonHeroPoint($user,['from' => $from, 'to' => $to]),
            'points_total' => floatval($totalPoints),
            'points_channel' => $pointService->calcPoint($user,['from' => $from, 'to' => $to]),
            'earning_channel' => floatval($earningAmount),
        ];

        return response()->json($result);
    }

    public function performanceMonthly(Request $request, PointService $pointService, User $user = null)
    {
        if (!$request->is('api/admin/*')){
            $user = auth('api')->user();
        }

        $result = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(12)->firstOfMonth()->format('Y-m-d'));
        $to = Arr::get($filters, 'to', (Carbon::now())->firstOfMonth()->format('Y-m-d'));
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $earningAmount = Earning::where('user_id', $user->id)
                ->whereDate('date', $month->startOfMonth()->format("Y-m-d"))
                ->sum('amount');

            $result[$month->format("Y-m")] = [
                'date' => $month->format("Y-m"),
                'points_hero' => $pointService->calcHeroPoint($user,['from' => $from_day, 'to' => $to_day]),
                'points_non_hero' => $pointService->calcNonHeroPoint($user,['from' => $from_day, 'to' => $to_day]),
                'points_total' => $pointService->calcPoint($user,['from' => $from_day, 'to' => $to_day]),
                'earning' => floatval($earningAmount),
            ];
        }

        return response()->json($result);
    }

    public function exportPublishersEarnings(Request $request)
    {
        $filters = $request->get('filters', []);
        $monthFilter = Arr::get($filters, 'month');

        $month = null;
        if ($monthFilter){
            $month = Carbon::parse($monthFilter);
        }

        $users = User::whereHas('channel')->get();

        foreach ($users as $user){

            $user->channelName = $user->channel->name?? '';

            $earning = Earning::where('user_id', $user->id)
                ->when(!empty($month), function ($query) use ($month) {
                    return $query->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month);
                })->first();
            $user->earningStatus = $earning?Earning::STATUS_TEXT[$earning->status]:'N/A';
            $user->earningAmount = $earning->amount?? 0;
        }

        $fileName = 'publishers-earnings'.((!empty($month))?'-'.$month->format('Y-m'):'').'.xlsx';

        return Excel::download(new PublisherEarningsExport($users), $fileName);
    }

}
