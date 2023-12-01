<?php

namespace App\Http\Controllers;

use Amir\Permission\Models\Role;
use App\Events\User\AccountDeleted;
use App\Events\UserVerified;
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
use App\Repository\Eloquent\ChannelRepository;
use App\Repository\Eloquent\TagRepository;
use App\Rules\CustomRule;
use App\Services\_2FAService;
use App\Services\EmailVerificationService;
use Carbon\Carbon;
use Elliptic\EC;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use kornrunner\Keccak;

class UserController extends Controller
{
    private $_2faService;
    private $EmailVerificationService;
    private $tagRepository;
    private $channelRepository;

    public function __construct(
        _2FAService $_2faService,
        EmailVerificationService $EmailVerificationService,
        TagRepository $tagRepository,
        ChannelRepository $channelRepository
    )
    {
        $this->_2faService = $_2faService;
        $this->EmailVerificationService = $EmailVerificationService;
        $this->tagRepository = $tagRepository;
        $this->channelRepository = $channelRepository;
    }

    public function index(Request $request)
    {
        $isAdminRoute = $request->is('api/admin/*');
        $isAdminList = $request->is('api/admin/admins');
        $isPublisherList = $request->is('api/admin/publishers');
        $isPublisherRequestsList = $request->is('api/admin/publisher-requests');


        $filters = $request->get('filters', []);
        $searchFilter = Arr::get($filters, 'search');
        $refFilter = Arr::get($filters, 'ref');
        $subFilter = Arr::get($filters, 'sub');
        $usernameFilter = Arr::get($filters, 'username');
        $emailFilter = Arr::get($filters, 'email');
        $isHeroFilter = Arr::get($filters, 'is_hero');
        $loginTypeFilter = Arr::get($filters, 'login_type');
        $isPublisherFilter = Arr::get($filters, 'is_publisher');
        $onlyDeletedFilter = Arr::get($filters, 'only_deleted');


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

        }elseif($isAdminRoute && ($refFilter || $subFilter)){
            $query = User::query();
        }else{
            $query = User::users();
        }


        if($searchFilter){
            $query->where(function ($query) use ($searchFilter){
                $query->where(function ($query) use ($searchFilter){
                    $query->SearchUsername($searchFilter);
                })->orWhere(function ($query) use ($searchFilter){
                    $query->SearchAuthWallet($searchFilter);
                })->orWhere(function ($query) use ($searchFilter){
                    $query->SearchEmail($searchFilter);
                })->orWhere(function ($query) use ($searchFilter){
                    $query->whereHas('channel', function($query) use ($searchFilter){
                        $query->searchTitle($searchFilter);
                    });
                });
            });
        }

        if($refFilter){
            $query->whereHas('referrer', function ($q) use ($refFilter){
                $q->where(function ($query) use ($refFilter){
                    $query->SearchUsername($refFilter);
                })->orWhere(function ($query) use ($refFilter){
                    $query->SearchEmail($refFilter);
                });
            });
        }

        if ($subFilter){
            $query->whereHas('subscribedChannels', function ($query) use ($subFilter){
                $query->whereHas('owner', function ($q) use ($subFilter){
                    $q->where(function ($query) use ($subFilter){
                        $query->SearchUsername($subFilter);
                    })->orWhere(function ($query) use ($subFilter){
                        $query->SearchEmail($subFilter);
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

        if($onlyDeletedFilter){
            $query->onlyTrashed();
        }

        if($isHeroFilter == "yes"){
            $query->IsHero();
        }elseif($isHeroFilter == "no"){
            $query->IsNonHero();
        }

        if($loginTypeFilter == "wallet"){
            $query->whereNotNull('auth_wallet');
        }elseif($loginTypeFilter == "email"){
            $query->whereNotNull('email');
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
        }elseif ($sort === 'last_active'){
            $query->orderBy('last_actived_at', 'desc');
        }elseif ($sort === 'most_subscribes'){
            $query->withCount('subscribedChannels')->orderBy('subscribed_channels_count', 'desc');
        }elseif ($sort === 'most_referrals'){
            $query->withCount('referrals')->orderBy('referrals_count', 'desc');
        }elseif ($sort === 'most_watch_hours'){
            $query->orderBy('watch_time', 'desc');
        }else{
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate();

        // Add Attributes
        if ($isAdminRoute){
            $users->append([
                'referrals_count',
                'auth_wallet',
                'tokenPointsTotalAmount',
                'tokenPointsLockedAmount',
            ]);
        }

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

        if($onlyDeletedFilter){
            $users->append(['deletion_feedback', 'deleted_at']);
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
            'auth_wallet',
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

    public function referralCodeCheck(Request $request)
    {
        $request->validate([
            'referral_code' => [
                'required', 'string', 'exists:users,referral_code',
            ],
        ],[
            'referral_code.exists' => 'Invalid code, please check and try again.'
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

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'feedback' => ['sometimes', 'string']
        ]);

        $user = auth('api')->user();

        $channel = $user->channel()->first();
        if ($channel){
            $this->channelRepository->softDelete($channel->id);
        }

        $user->deletion_feedback = $request->get('feedback');
        $user->save();
        $user->delete();

        $token = sha1($user->id . time());

        $accountDeletion = new AccountDeletion();
        $accountDeletion->user_id = $user->id;
        $accountDeletion->token = $token;
        $accountDeletion->expired_at = Carbon::now()->addDays(180);
        $accountDeletion->save();

        $link = route('account.restore', ['token' => $token]);

        Mail::to($user->email)
            ->queue(new DeleteAccountMail($link));


        event(new AccountDeleted($user));

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function restoreAccount($token)
    {
        $accountDeletion = AccountDeletion::where('token', $token)->where('expired_at', '>', Carbon::now())->firstOrFail();

        $user = $accountDeletion->user()->onlyTrashed()->firstOrFail();

        $user->restore();

        $channel = $user->channel()->onlyTrashed()->first();
        if ($channel){
            $this->channelRepository->Restore($channel->id);
        }

        $accountDeletion->delete();

        return response()->json(['status' => 'ok']);
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
            //'favoriteCryptoCurrencies',
            'verifiedPaymentDetails',
            'lastPaymentDetails',
        ])->append([
            'eth_address',
            'auth_wallet',
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
            'isHeroMembershipAutoRenewal',
            'channelAutoImportIsActive',
            'favoriteCryptoCurrenciesCount',
        ]);

        if ($user->channel){
            $user->channel->append(['subscribers_count', 'monetization_qualified_at', 'youtube_next_scrap_at']);
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
        $user = auth('api')->user();

        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        $request->validate([
            'username' => [
                'nullable', 'string', 'between:4,14',
                CustomRule::forbiddenWords($forbiddenWords),
                CustomRule::uniqueTrimmed(User::PUNCTUATION_MARKS, 'users', 'username')
                    ->ignore(auth('api')->id()),
                function ($attribute, $value, $fail) use($user) {
                    if (
                        $value
                        && $user->username
                        && !$user->channel
                        && $user->is_hero
                        && (($meta = $user->meta()->where('key', UserMeta::UserNameChangedAt)->first()) && $meta->value > Carbon::now()->subMonths(3))
                    ){
                        $fail('You can only change your username once every 3 months.');
                    }
                },
            ],
            //'email' => 'nullable|email',
            'avatar' => 'nullable|string',
            'current_password' => 'nullable|string|password|required_with:new_password',
            'new_password' => 'nullable|string|min:6|max:32|required_with:current_password',
            'scope' => 'required_with:eth_address',
            'tag_names' => ['nullable', 'array'],
            'tag_names.*' => ['string', CustomRule::forbiddenWords($forbiddenWords), CustomRule::alphaSpace(), 'max:25'],
        ]);


        if (
            !$user->username ||
            (!$user->channel
            && $user->is_hero
            && (!($meta = $user->meta()->where('key', UserMeta::UserNameChangedAt)->first()) || $meta->value <= Carbon::now()->subMonths(3)))
        ){
            $user->username = $request->get('username', $user->username);
            $user->meta()->updateOrCreate(
                ['key' => UserMeta::UserNameChangedAt],
                ['value' => Carbon::now()]
            );
        }


        if (!$user->channel){
            $user->avatar_url = $request->get('avatar', $user->avatar_url);
        }

        if($request->get('new_password')){
            $user->password = Hash::make($request->get('new_password'));
        }

        if(is_array($request->input('tag_names'))){
            $tagIds = [];
            foreach ($request->get('tag_names') as $tagName){
                $tag = $this->tagRepository->store([
                    'name' => $tagName,
                    'status' => Tag::STATUS_PUBLISHED,
                    'creation_scope' => Tag::CREATION_SCOPE_USER,
                ]);

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

    public function setAuthWallet(Request $request)
    {

        $request->validate([
            'message' => ['required'],
            'address' => [
                'required', 'regex:/^0x[a-fA-F0-9]{40}$/', Rule::unique('users', 'auth_wallet'),
                function ($attribute, $signature, $fail) {
                    if (auth('api')->user()->auth_wallet){
                        $fail('The '.$attribute.' is already assigned to user.');
                    }
                }
            ],
            'signature' => [
                'required',
                function ($attribute, $signature, $fail) {
                    $message = request()->get('message');
                    $address = request()->get('address');

                    try {
                        $messageLength = strlen($message);
                        $hash = Keccak::hash("\x19Ethereum Signed Message:\n{$messageLength}{$message}", 256);
                        $sign = [
                            "r" => substr($signature, 2, 64),
                            "s" => substr($signature, 66, 64)
                        ];

                        $recId  = ord(hex2bin(substr($signature, 130, 2))) - 27;

                        if ($recId != ($recId & 1)) {
                            throw new Exception();
                        }

                        $publicKey = (new EC('secp256k1'))->recoverPubKey($hash, $sign, $recId);

                        if ("0x" . substr(Keccak::hash(substr(hex2bin($publicKey->encode("hex")), 1), 256), 24) !== Str::lower($address)) {
                            throw new Exception();
                        }
                    }catch (Exception $e){
                        $fail('The '.$attribute.' is invalid.');
                    }
                },
            ],
        ]);

        $user = auth('api')->user();
        $user->auth_wallet = $request->get('address');
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
