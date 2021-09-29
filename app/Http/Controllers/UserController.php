<?php

namespace App\Http\Controllers;

use App\Exports\PublisherEarningsExport;
use App\Http\Requests\UserStore;
use App\Http\Resources\Channel\ChannelSubscriberCollection;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserDetails;
use App\Http\Resources\UserItem;
use App\Mail\ETHAddressConfirmationMail;
use App\Models\Department;
use App\Models\Earning;
use App\Models\Message;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\VideoStatisticsDaily;
use App\Services\PointService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return UserCollection
     */
    public function index(Request $request)
    {
        if ($request->is('api/admin/admins')){
            $query = User::admins();
        }elseif ($request->is('api/admin/publishers')){
            $query = User::publishers();
        }elseif ($request->is('api/admin/publisher-requests')){
            $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Applications'])->id;

            $publisherRequestUserId = Message::where([
                    'department_id' => $publisherApplicationDepartmentId
                ]
            )->select('user_id')->get()->pluck('user_id')->unique()->filter(function ($value) { return !is_null($value); })->toArray();
            $query = User::whereIn('id', $publisherRequestUserId);
        }else{
            $query = User::query();
        }

        $filters = $request->get('filters', []);

        $searchFilter = Arr::get($filters, 'search');

        $usernameFilter = Arr::get($filters, 'username');

        $emailFilter = Arr::get($filters, 'email');

        $isHeroFilter = Arr::get($filters, 'is_hero');

        $isPublisherFilter = Arr::get($filters, 'is_publisher');

        if($searchFilter){
            $query->where(function ($query) use ($searchFilter){
                $query->where(function ($query) use ($searchFilter){
                    $query->SearchUsername($searchFilter);
                })->orWhere(function ($query) use ($searchFilter){
                    $query->SearchEmail($searchFilter);
                })->orWhere(function ($query) use ($searchFilter){
                    $query->whereHas('channel', function($query) use ($searchFilter){
                        $query->searchTitle($searchFilter);
                    });
                });
            });
        }

        if($usernameFilter){
            $query->SearchUsername($usernameFilter);
        }

        if($emailFilter){
            $query->SearchEmail($emailFilter);
        }

        if($isHeroFilter == "yes"){
            $query->IsHero();
        }elseif($isHeroFilter == "no"){
            $query->IsNonHero();
        }

        if($isPublisherFilter == "yes"){
            $query->Publishers();
        }elseif($isPublisherFilter == "no"){
            $query->NotPublishers();
        }

        $sort = $request->get('sort');
        if($sort === 'most_like'){
            $query->withCount(['likedVideos', 'dislikedVideos'])->orderByRaw('(liked_videos_count - disliked_videos_count) DESC');
        }elseif ($sort === 'most_comment'){
            $query->withCount('comments')->orderBy('comments_count', 'desc');
        }

        $users = $query->paginate();

        return \App\Http\Resources\User\UserItem::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserStore $request)
    {
        $user = new User();

        $user->email = $request->get("email");
        $user->username = $request->get("username");
        $user->status = User::STATUS_ACTIVE;
        $user->email_verified_at = now();
        $user->role_id = $request->get("role_id");
        $user->avatar = $request->get('avatar');
        $user->eth_address = $request->get('eth_address');

        $user->password = Hash::make(rand(100000,1000000000));
        // TODO: send reset password link here

        $user->save();

        return response()->json(new UserItem($user));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return UserDetails::make($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'username' => 'nullable|string|alpha_dash',
            'email' => 'nullable|email',
            'avatar' => 'nullable|string',
            'eth_address' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|max:32',
            'hero_due_at' => 'nullable|date',
            'muted_until' => [
                'nullable',
                Rule::in(User::MUTED_UNTIL_TEXT),
            ],
        ]);

        $user->username = $request->get('username', $user->username);
        $user->email = $request->get('email', $user->email);

        $user->avatar = $request->get('avatar', $user->avatar);

        if($request->get('new_password')){
            $user->password = Hash::make($request->get('new_password'));
        }

        // For Admin permissions
        if ($request->is('api/admin/users/'.$user->id)){
            // Is mute
            $user->is_mute = $request->get('is_mute', $user->is_mute);

            if ($request->get('is_mute', $user->is_mute) && $request->get('muted_until') && $request->get('muted_until') != User::MUTE_PERMANENT){
                $user->muted_until = Carbon::now()->addSeconds(array_flip(User::MUTED_UNTIL_TEXT)[$request->get('muted_until')]);
            }else{
                $user->muted_until = null;
            }

            // hero member
            $user->hero_due_at = $request->get('hero_due_at', null);

            if (!$user->hero_member_at && $user->is_hero){
                $user->hero_member_at = Carbon::now();
            }
        }

        if ($request->is('api/admin/*')){

            $user->eth_address = $request->get('eth_address', $request->eth_address);

        }elseif($request->get('eth_address') && $user->eth_address != $request->get('eth_address')){
            // Add new value to user meta and send confirmation email
            $user->meta()->updateOrCreate(
                ['key' => UserMeta::NEW_ETH_ADDRESS_KEY],
                ['value' => $request->get('eth_address'),]
            );

            $token = sha1($user->id . time());
            $user->meta()->updateOrCreate(
                ['key' => UserMeta::NEW_ETH_ADDRESS_VERIFICATION_CODE_KEY],
                ['value' => $token]
            );

            $link = config('general.ETH_ADDRESS_CONFIRMATION_URL') . $token;
            Mail::to($user->email)
                ->queue(new ETHAddressConfirmationMail($link));
        }

        $user->save();

        return response()->json(new UserItem($user));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'general.successful'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        $user = Auth::user();

        return response()->json(new UserItem($user->load('role')));

    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'username' => 'nullable|string|alpha_dash',
            //'email' => 'nullable|email',
            'avatar' => 'nullable|string',
            'current_password' => 'nullable|string|password|required_with:new_password',
            'new_password' => 'nullable|string|min:6|max:32|required_with:current_password',
        ]);

        $user = auth('api')->user();

        $user->username = $request->get('username', $user->username);

        $user->avatar = $request->get('avatar', $user->avatar);

        if($request->get('new_password')){
            $user->password = Hash::make($request->get('new_password'));
        }

        if($request->get('eth_address') && $user->eth_address != $request->get('eth_address')){
            // Add new value to user meta and send confirmation email
            $user->meta()->updateOrCreate(
                ['key' => UserMeta::NEW_ETH_ADDRESS_KEY],
                ['value' => $request->get('eth_address')]
            );

            $token = sha1($user->id . time());
            $user->meta()->updateOrCreate(
                ['key' => UserMeta::NEW_ETH_ADDRESS_VERIFICATION_CODE_KEY],
                ['value' => $token]
            );

            $link = config('general.ETH_ADDRESS_CONFIRMATION_URL') . $token;
            Mail::to($user->email)
                ->queue(new ETHAddressConfirmationMail($link));
        }

        $user->save();

        return response()->json(new UserItem($user));

    }

    public function changeETHAddressConfirmation($token)
    {
        $meta = UserMeta::where([
            'key' => UserMeta::NEW_ETH_ADDRESS_VERIFICATION_CODE_KEY,
            'value' => $token,
        ])->firstOrFail();

        $user = $meta->user;
        $new_eth_address = $user->meta()->where('key', UserMeta::NEW_ETH_ADDRESS_KEY)->firstOrFail();

        $user->eth_address = $new_eth_address->value;
        $user->save();

        $user->meta()->where('key', UserMeta::NEW_ETH_ADDRESS_KEY)->delete();
        $user->meta()->where('key', UserMeta::NEW_ETH_ADDRESS_VERIFICATION_CODE_KEY)->delete();

        return response()->json(['message' => __('users.messages.eth_address_confirmed')], 200);
    }

    public function subscribedChannels()
    {
        $per_page = request()->get('per_page') ?: 15;

        return ChannelSubscriberCollection::make(auth('api')->user()->subscribedChannels()->paginate($per_page));
    }

    public function userPoints(Request $request,PointService $pointService, User $user = null)
    {
        if (!$request->is('api/admin/*')){
            $user = auth('api')->user();
        }

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->firstOfMonth());
        $to = Arr::get($filters, 'to', Carbon::now());


        $points = $pointService->calcPoint($user,['from' => $from, 'to' => $to]);

        return response()->json(['points' => $points]);
    }

    public function userMonthlyPoints(Request $request, PointService $pointService, User $user = null)
    {
        if (!$request->is('api/admin/*')){
            $user = auth('api')->user();
        }

        $points = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(12)->firstOfMonth());
        $to = Arr::get($filters, 'to', (Carbon::now())->firstOfMonth());
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $earning = Earning::where('user_id', $user->id)
                ->whereDate('date', $month->startOfMonth()->format("Y-m-d"))
                ->first();

            $points[$month->format("Y-m")] = [
                'hero' => $pointService->calcHeroPoint($user,['from' => $from_day, 'to' => $to_day]),
                'non_hero' => $pointService->calcNonHeroPoint($user,['from' => $from_day, 'to' => $to_day]),
                'total' => $pointService->calcPoint($user,['from' => $from_day, 'to' => $to_day]),
                'earning' => $earning? $earning->amount : 0,
            ];
        }

        return response()->json(['points' => $points]);
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
