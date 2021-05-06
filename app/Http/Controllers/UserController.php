<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStore;
use App\Http\Resources\Channel\ChannelSubscriberItem;
use App\Http\Resources\ChannelSubscriberCollection;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserDetails;
use App\Http\Resources\UserItem;
use App\Models\Department;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

        $usernameFilter = Arr::get($filters, 'username');

        $emailFilter = Arr::get($filters, 'email');

        $isHeroFilter = Arr::get($filters, 'is_hero');

        $isPublisherFilter = Arr::get($filters, 'is_publisher');

        if($usernameFilter){
            $query->SearchUsername($usernameFilter);
        }

        if($emailFilter){
            $query->SearchEmail($emailFilter);
        }

        if($isHeroFilter == "yes"){
            $query->IsHero();
        }elseif($isHeroFilter == "no"){
            $query->IsNotHero();
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

        return UserCollection::make($users);
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

        $user->eth_address = $request->get('eth_address', $request->eth_address);

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

        return response()->json(new UserItem($user));

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
            'current_password' => 'nullable||string|password|required_with:new_password',
            'new_password' => 'nullable|string|min:6|max:32|required_with:current_password',
        ]);

        $user = Auth::user();

        $user->username = $request->get('username', $user->username);

        $user->avatar = $request->get('avatar', $user->avatar);

        if($request->get('new_password')){
            $user->password = Hash::make($request->get('new_password'));
        }

        $user->eth_address = $request->get('eth_address', $request->eth_address);

        $user->save();

        return response()->json(new UserItem($user));

    }

    public function subscribedChannels()
    {
        return ChannelSubscriberCollection::make(auth('api')->user()->subscribedChannels);
    }

}
