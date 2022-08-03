<?php

namespace App\Http\Controllers;

use Amir\Permission\Models\Role;
use App\Events\User\AccountDeleted;
use App\Http\Requests\UserStore;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\Video\VideoResource;
use App\Mail\DeleteAccountMail;
use App\Mail\ETHAddressConfirmationMail;
use App\Mail\PasswordResetMail;
use App\Models\_2FA;
use App\Models\AccountDeletion;
use App\Models\Channel;
use App\Models\Option;
use App\Models\PasswordReset;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserMeta;
use App\Rules\CustomRule;
use App\Services\_2FAService;
use App\Services\EmailVerificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private $_2faService;
    private $EmailVerificationService;

    public function __construct(_2FAService $_2faService, EmailVerificationService $EmailVerificationService)
    {
        $this->_2faService = $_2faService;
        $this->EmailVerificationService = $EmailVerificationService;
    }

    public function index(Request $request)
    {
        $isAdminList = $request->is('api/admin/admins');
        $isPublisherList = $request->is('api/admin/publishers');
        $isPublisherRequestsList = $request->is('api/admin/publisher-requests');

        if ($isAdminList){
            $query = User::admins();
        }elseif ($isPublisherList){
            $query = User::publishers();
        }elseif ($isPublisherRequestsList){

            $query = User::whereHas('meta', function ($q) use($request) {
                $publisherRequestFilter = Arr::get($request->get('filters', []), 'status');

                $q->where('key', UserMeta::PUBLISHER_REQUEST_STATUS);
                if ($publisherRequestFilter){
                    $q->where('value', $publisherRequestFilter);
                }
            })->whereNull('role_id');

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

        // Add Attributes
        if ($isAdminList){
            // Nothing
        }elseif ($isPublisherList){
            $users->load(['channel']);
        }elseif ($isPublisherRequestsList){
            $users->append([
                'publisher_request',
                'publisher_request_details',
            ]);
        }else{
            $users->append([
                'role_name',
                'liked_videos_count',
                'disliked_videos_count',
                'comments_count',
                'subscribed_channels_count',
            ]);
        }

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserStore $request)
    {
        $adminRole = Role::firstOrCreate(['name' => User::ADMIN_ROLE]);
        $publisherRole = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE]);

        $userQuery = User::where('email', $request->get('email'))->whereNull('email_verified_at');

        if ($request->is('api/admin/admins')){
            $userQuery->where('role_id', $adminRole->id);
        }else{
            $userQuery->where(function($q) use($publisherRole) {
                $q->whereNull('role_id')
                    ->orWhere('role_id', $publisherRole->id);
            });
        }

        $user = $userQuery->first();

        if(is_null($user)){
            $user = new User();
        }

        $user->email = $request->get("email");
        $user->username = $request->get("username");
        $user->status = User::STATUS_ACTIVE;
        $user->email_verified_at = now();
        $user->avatar_url = $request->get('avatar');
        $user->eth_address = $request->get('eth_address');
        $user->password = Hash::make(rand(100000,1000000000));

        if ($request->is('api/admin/admins')){
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

        return VideoResource::make($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $user->load([
            'channel',
            'subscribedChannels',
            'referrer',
            'referrals',
            'meta',
            'favoriteTags',
            'favoriteCryptoCurrencies',
            'bookmarkVideos',
        ])->append([
            'eth_address',
            'role_name',
            'liked_videos_count',
            'disliked_videos_count',
            'bookmarked_videos_count',
            'comments_count',
            'subscribed_channels_count',
            'publisher_request',
            'publisher_request_details',
            'is_conversion',
            'loyalty_points',
        ]);

        return UserResource::make($user);
    }

    public function usernameCheck(Request $request)
    {
        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        $request->validate([
            'username' => [
                'required', 'string', 'between:4,14',
                CustomRule::forbiddenWords($forbiddenWords),
                CustomRule::uniqueTrimmed(User::PUNCTUATION_MARKS, 'users', 'username')
                    ->ignore(auth('api')->id())
            ],
        ]);

        return response()->json(['status' => 'ok']);
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
                'nullable', 'string', 'between:4,14',
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

        $user->email = $request->get('email', $user->email);

        if (!$user->channel){
            $user->username = $request->get('username', $user->username);
            $user->avatar_url = $request->get('avatar', $user->avatar_url);
        }

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

        return UserResource::make($user);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user = null)
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

    public function deleteAccountRequest(Request $request)
    {
        $user = auth('api')->user();

        $token = sha1($user->id . time());


        $accountDeletion = AccountDeletion::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token' => $token,
                'created_at' => Carbon::now(),
                'expired_at' => Carbon::now()->addMinutes(45)
            ],
        );

        if ($request->is('api/publisher/*')){
            $link = config('general.PUBLISHER_ACCOUNT_DELETION_URL') . $token;
        }else{
            $link = config('general.MWA_ACCOUNT_DELETION_URL') . $token;
        }

        Mail::to($user->email)
            ->queue(new DeleteAccountMail($link));

        return response()->json([
            'status' => 'ok',
            'email' => $user->email,
            'message' => __('account.account_deletion_link_sent'),
        ]);
    }

    public function deleteAccount($token)
    {
        $accountDeletion = AccountDeletion::where('token', $token)
            ->where('expired_at', '>', Carbon::now())
            ->firstOrFail();

        $user = User::findOrFail($accountDeletion->user_id);

        $channel = $user->channel()->first();
        if ($channel){
            $channel->status = Channel::STATUS_FREEZE;
            $channel->save();
        }

        $user->delete();

        $accountDeletion->expired_at = now();
        $accountDeletion->save();

        event(new AccountDeleted($user));

        return response()->json([
            'status' => 'ok',
            'message' => 'general.successful'
        ]);
    }

    public function restoreUser($id)
    {
        $user = User::onlyTrashed()->where('id', $id)->firstOrFail();

        $user->restore();

        $channel = $user->channel()->first();
        if ($channel){
            $channel->status = Channel::STATUS_PUBLISHED;
            $channel->save();
        }

        return response()->json([
            'status' => 'ok',
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
        $user = auth('api')->user();

        $user->load([
            'channel',
            'referrer',
            'meta',
            'favoriteTags',
            'favoriteCryptoCurrencies',
        ])->append([
            'eth_address',
            'role_name',
            'liked_videos_count',
            'disliked_videos_count',
            'bookmarked_videos_count',
            'comments_count',
            'subscribed_channels_count',
            'publisher_request',
            'publisher_request_details',
            'is_conversion',
            'loyalty_points',
        ]);

        if ($user->channel){
            $user->channel->append(['subscribers_count']);
        }

        return UserResource::make($user);
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
                'nullable', 'string', 'between:4,14',
                CustomRule::forbiddenWords($forbiddenWords),
                CustomRule::uniqueTrimmed(User::PUNCTUATION_MARKS, 'users', 'username')
                    ->ignore(auth('api')->id()),
            ],
            //'email' => 'nullable|email',
            'avatar' => 'nullable|string',
            'current_password' => 'nullable|string|password|required_with:new_password',
            'new_password' => 'nullable|string|min:6|max:32|required_with:current_password',
            'scope' => 'required_with:eth_address',
            'tag_names' => ['nullable', 'array'],
            'tag_names.*' => ['string', CustomRule::forbiddenWords($forbiddenWords)],
        ]);

        $user = auth('api')->user();

        if (!$user->channel){
            $user->username = $request->get('username', $user->username);
            $user->avatar_url = $request->get('avatar', $user->avatar_url);
        }

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

        $user->load([
            'channel',
            'referrer',
            'meta',
            'favoriteTags',
            'favoriteCryptoCurrencies',
        ])->append([
            'eth_address',
            'role_name',
            'liked_videos_count',
            'disliked_videos_count',
            'bookmarked_videos_count',
            'comments_count',
            'subscribed_channels_count',
            'publisher_request',
            'publisher_request_details',
            'is_conversion',
            'loyalty_points',
        ]);

        if ($user->channel){
            $user->channel->append(['subscribers_count']);
        }

        return UserResource::make($user);

    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|password|required_with:new_password',
            'new_password' => 'required|string|min:6|max:32|required_with:current_password',
        ]);

        $user = auth('api')->user();

        $_2fa = $user->_2fa;

        if ($_2fa && ($_2fa->app_status || $_2fa->email_status)){
            // 2FA verification
            $errors = [];
            $_2faResult = $this->_2faService->check2FA($user, ['ip' => $request->ip()]);

            if (($_2fa->app_status && !$_2faResult['app']) || ($_2fa->email_status && !$_2faResult['email'])){
                $errors['app'] = $_2fa->app_status? 'Please verify app 2FA' : null;
                $errors['email'] = $_2fa->email_status? 'Please verify email 2FA' : null;
            }

            if (!empty($errors)){
                return response()->json([
                    'message' => 'Please verify 2FA',
                    'code' => '2fa.require',
                    'errors' => $errors
                ], 403);
            }

        }else if (!$this->EmailVerificationService->check($user)){
            // Email Verification
            $this->EmailVerificationService->sendCode($user);
            return response()->json([
                'message' => 'Please pass email verification',
                'code' => 'email_verification.require',
            ], 403);
        }

        $user->password = Hash::make($request->get('new_password'));
        $user->save();

        return response()->json(['status' => 'ok']);
    }

    public function changeETHAddress(Request $request)
    {
        $request->validate([
            //'current_password' => 'required|string|password',
            'eth_address' => 'required|regex:/^0x[a-fA-F0-9]{40}$/',
            'scope' => 'required',
        ]);

        $user = auth('api')->user();

        $_2fa = $user->_2fa;

        if ($_2fa && ($_2fa->app_status || $_2fa->email_status)){
            // 2FA verification
            $errors = [];
            $_2faResult = $this->_2faService->check2FA($user, ['ip' => $request->ip()]);

            if (($_2fa->app_status && !$_2faResult['app']) || ($_2fa->email_status && !$_2faResult['email'])){
                $errors['app'] = $_2fa->app_status? 'Please verify app 2FA' : null;
                $errors['email'] = $_2fa->email_status? 'Please verify email 2FA' : null;
            }

            if (!empty($errors)){
                return response()->json([
                    'message' => 'Please verify 2FA',
                    'code' => '2fa.require',
                    'errors' => $errors
                ], 403);
            }

        }else if (!$this->EmailVerificationService->check($user)){
            // Email Verification
            $this->EmailVerificationService->sendCode($user);
            return response()->json([
                'message' => 'Please pass email verification',
                'code' => 'email_verification.require',
            ], 403);
        }

        $user->eth_address = $request->get('eth_address');
        $user->save();

        return response()->json(['status' => 'ok']);
    }

    public function subscribedChannels()
    {
        $per_page = request()->get('per_page') ?: 15;

        $channels = auth('api')->user()->subscribedChannels()->paginate($per_page);

        $channels->append(['is_subscribed', 'subscribers_count']);

        return ChannelResource::collection($channels);
    }
}
