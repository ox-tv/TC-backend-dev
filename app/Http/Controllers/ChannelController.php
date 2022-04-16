<?php

namespace App\Http\Controllers;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\Channels\ChannelUpdated;
use App\Events\ChannelSubscribed;
use App\Exports\PublisherEarningsExport;
use App\Http\Requests\ChannelImportRequest;
use App\Http\Requests\ChannelStore;
use App\Http\Requests\ChannelUpdate;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Channel\ImportRequestsCollection;
use App\Models\Channel;
use App\Models\Earning;
use App\Models\User;
use App\Models\VideoStatisticsDaily;
use App\Services\PointService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ChannelController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->get('per_page') ?: 15;
        $isAdmin = $request->is('api/admin/channels');

        if($isAdmin){
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
            $query->withCount(['videos'=> function($q){$q->distinct('video_id');}])->orderBy('videos_count', 'desc');
        }elseif ($sort === 'most_subscribers'){
            $query->withCount('subscribers')->orderBy('subscribers_count', 'desc');
        }elseif ($sort === 'most_points'){
            $query->orderBy('points', 'desc');
        }elseif ($sort === 'most_comments'){
            $query->withCount('comments')->orderBy('comments_count', 'desc');
        }

        $channels = $query->paginate($perPage);

        if ($isAdmin){
            $channels->append(['subscribers_count', 'uploads_count', 'total_views', 'total_likes', 'total_comments']);
        }else{
            $channels->append(['is_subscribed', 'subscribers_count']);
        }

        return ChannelResource::collection($channels);
    }

    public function show(Request $request, $idOrSlug = null)
    {
        $adminPanel = $request->is('api/admin/*');
        $publisherPanel = $request->is('api/publisher/*');

        $channel = Channel::when($idOrSlug, function ($q, $idOrSlug){
                $q->idOrSlug($idOrSlug);
            })
            ->when(!$idOrSlug, function ($q){
                $q->where('user_id', auth('api')->id());
            })
            ->firstOrFail();

        if ($adminPanel || $publisherPanel){
            $channel->append([
                'is_subscribed',
                'subscribers_count',
                'uploads_count',
                'total_views',
                'watch_time',
                'total_likes',
                'total_dislikes',
                'total_comments',
                'hero_subscribers_count',
            ]);

            if ($adminPanel){
                $channel->load(['owner']);
            }
        }else{
            $channel->append(['is_subscribed', 'subscribers_count']);
        }

        return ChannelResource::make($channel);
    }

    public function store(ChannelStore $request)
    {
        $channel = new Channel();

        $channel->name = $request->get('name');
        $channel->description = $request->get('description');
        $channel->slug = Str::slug($request->get('name'));

        $channel->cover_url = $request->get('cover');
        $channel->avatar_url = $request->get('avatar');

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

        $user = $channel->owner;
        $user->username = $channel->name;
        $user->avatar_url = $channel->avatar_url;
        $user->save();

        return ChannelResource::make($channel);

    }

    public function update(ChannelUpdate $request, Channel $channel)
    {
        if(is_null($request->route('channel'))){
            $channel = auth('api')->user()->channel;
        }

        if(!$request->is('api/admin/channels/*') && $channel->owner->id != auth('api')->id()){
            abort(403, 'You do not have permission to access');
        }

        $oldChannel = clone $channel;

        $channel->name = $request->get('name', $channel->name);
        $channel->description = $request->get('description', $channel->description);

        $channel->slug = $request->get('slug')? $request->get('slug'): Str::slug($request->get('name', $channel->name));

        $channel->cover_url = $request->get('cover', $channel->cover_url);
        $channel->avatar_url = $request->get('avatar', $channel->avatar_url);

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
        }

        if($request->is('api/admin/channels/*') && $request->get('points')){
            $channel->points = $request->get('points');
        }

        $channel->save();

        $user = $channel->owner;
        $user->username = $channel->name;
        $user->avatar_url = $channel->avatar_url;
        $user->save();

        event(new ChannelUpdated($oldChannel, $channel));

        return ChannelResource::make($channel);
    }

    public function destroy(Channel $channel)
    {
        $channel->delete();
    }

    public function subscription(Channel $channel){

        $user = Auth::user();

        $subscribedBefore = $channel->subscribers()->where('id', $user->id)->exists();

        if($subscribedBefore){
            $channel->subscribers()->detach(auth('api')->user());
        }else{
            $channel->subscribers()->attach(auth('api')->user());
        }

        event(new ChannelSubscribed(
            $channel,
            auth('api')->user(),
            $subscribedBefore?0:1,
            $subscribedBefore?1:0));

        return ChannelResource::make($channel);
    }

    public function importRequest(ChannelImportRequest $request, Channel $channel){

        $channel->import_request_status = Channel::IMPORT_STATUS_REQUESTED;
        $channel->youtube_channel_id = $request->get("youtube_channel_id");
        $channel->save();

        event(new ChannelImportRequestAccepted($channel));

        return response()->json([
            'message' => __('channel.messages.import_request_submitted'),
        ]);
    }

    public function importRequests()
    {
        $requests = Channel::where('import_request_status', Channel::IMPORT_STATUS_REQUESTED)->get();

        return ImportRequestsCollection::make($requests);
    }

    public function importCompleted(Channel $channel)
    {
        $channel->import_request_status = Channel::IMPORT_STATUS_COMPLETED;
        $channel->save();

        event(new ChannelImportRequestCompleted($channel));

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
