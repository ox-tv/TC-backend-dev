<?php

namespace App\Http\Controllers;

use Amir\Permission\Models\Role;
use App\Http\Requests\UserStore;
use App\Http\Resources\Channel\ChannelSubscriberCollection;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserDetails;
use App\Http\Resources\UserItem;
use App\Mail\ETHAddressConfirmationMail;
use App\Mail\PasswordResetMail;
use App\Models\Department;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\Option;
use App\Models\PasswordReset;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserMeta;
use App\Rules\CustomRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        }elseif ($request->is('api/admin/publishers')){
            $query = User::publishers();
        }elseif ($request->is('api/admin/publisher-requests')){
            $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Applications'])->id;

            $publisherRequestUserId = Message::where([
                    'department_id' => $publisherApplicationDepartmentId,
                ]
            )->whereHas('users', function ($q){
                $q->where('message_user.status', '!=', MessageUser::STATUS_CLOSE);
            })->select('user_id')->get()->pluck('user_id')->unique()->filter(function ($value) { return !is_null($value); })->toArray();
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
        }elseif ($sort === 'email'){
            $query->orderBy('email');
        }elseif ($sort === 'username'){
            $query->orderBy('username');
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
        $user = User::where('email', $request->get('email'))->whereNull('email_verified_at')->first();

        if(is_null($user)){
            $user = new User();
        }

        $user->email = $request->get("email");
        $user->username = $request->get("username");
        $user->status = User::STATUS_ACTIVE;
        $user->email_verified_at = now();
        $user->avatar = $request->get('avatar');
        $user->eth_address = $request->get('eth_address');
        $user->password = Hash::make(rand(100000,1000000000));

        if ($request->is('api/admin/admins')){
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $user->role_id = $adminRole->id;
        }

        $user->save();

        // send reset password link here
        $token = sha1($user->id . time());

        $reset_password = new PasswordReset();
        $reset_password->email = $user->email;
        $reset_password->token = $token;
        $reset_password->save();

        if ($request->is('api/admin/admins')){
            $link = config('general.ADMIN_PASSWORD_RESET_URL') . $token;
        }else{
            $link = config('general.MWA_PASSWORD_RESET_URL') . $token;
        }

        Mail::to($user->email)
            ->queue(new PasswordResetMail($link));

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
        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        $request->validate([
            'username' => [
                'nullable', 'string',
                CustomRule::forbiddenWords($forbiddenWords),
                CustomRule::uniqueTrimmed(User::PUNCTUATION_MARKS, 'users', 'username')
                    ->ignore($user->id)
            ],
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
        // remove user relations
        $channel = $user->channel()->first();
        if ($channel){
            $channel->videos()->delete();
            $channel->delete();
        }

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
        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        $request->validate([
            'username' => [
                'nullable', 'string',
                CustomRule::forbiddenWords($forbiddenWords),
                CustomRule::uniqueTrimmed(User::PUNCTUATION_MARKS, 'users', 'username')
                    ->ignore(auth('api')->id()),
            ],
            //'email' => 'nullable|email',
            'avatar' => 'nullable|string',
            'current_password' => 'nullable|string|password|required_with:new_password',
            'new_password' => 'nullable|string|min:6|max:32|required_with:current_password',
            'scope' => 'required_with:eth_address',
            'tag_names' => ['nullable', 'array', CustomRule::forbiddenWords($forbiddenWords)],
        ]);

        $user = auth('api')->user();

        $user->username = $request->get('username', $user->username);

        $user->avatar = $request->get('avatar', $user->avatar);

        if($request->get('new_password')){
            $user->password = Hash::make($request->get('new_password'));
        }

        if(is_array($request->input('tag_names'))){
            $tagIds = [];
            foreach ($request->get('tag_names') as $tagName){
                $tag = Tag::firstOrCreate(
                    ['name' => $tagName],
                    ['status' => Tag::STATUS_PUBLISHED, 'creation_scope' => Tag::CREATION_SCOPE_USER]
                );

                $tagIds[] = $tag->id;
            }
            $user->favoriteTags()->sync($tagIds);
        }

        $user->save();

        return response()->json(new UserItem($user));

    }

    public function changeETHAddress(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|password',
            'eth_address' => 'required|regex:/^0x[a-fA-F0-9]{40}$/',
            'scope' => 'required',
        ]);

        $user = auth('api')->user();

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

        $link = (
            $request->get('scope') == 'publisher'?
                config('general.PUBLISHER_ETH_ADDRESS_CONFIRMATION_URL')
                : config('general.MWA_ETH_ADDRESS_CONFIRMATION_URL')
            ) . $token;
        Mail::to($user->email)
            ->queue(new ETHAddressConfirmationMail($link));

        return response()->json(['status' => 'ok']);
    }

    public function changeETHAddressConfirmation($token)
    {
        $meta = UserMeta::where([
            'key' => UserMeta::NEW_ETH_ADDRESS_VERIFICATION_CODE_KEY,
            'value' => $token,
        ])->firstOrFail();

        $user = $meta->user;
        $newETHAddress = $user->meta()->where('key', UserMeta::NEW_ETH_ADDRESS_KEY)->firstOrFail();

        $user->eth_address = $newETHAddress->value;
        $user->save();

        $user->meta()->where('key', UserMeta::NEW_ETH_ADDRESS_KEY)->delete();
        $user->meta()->where('key', UserMeta::NEW_ETH_ADDRESS_VERIFICATION_CODE_KEY)->delete();

        return response()->json(['status' => 'ok']);
    }

    public function subscribedChannels()
    {
        $per_page = request()->get('per_page') ?: 15;

        return ChannelSubscriberCollection::make(auth('api')->user()->subscribedChannels()->paginate($per_page));
    }
}
