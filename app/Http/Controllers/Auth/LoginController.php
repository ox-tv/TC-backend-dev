<?php

namespace App\Http\Controllers\Auth;

use Amir\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\User\UserResource;
use App\Mail\MagicLoginMail;
use App\Mail\PasswordResetMail;
use App\Models\AuthKey;
use App\Models\MagicLogin;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\_2FAService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    private $_2faService;

    public function __construct(_2FAService $_2faService)
    {
        $this->_2faService = $_2faService;
    }

    public function login(LoginRequest $request, $scope = 'user')
    {
        $login = $request->get('email')? : $request->get('login');
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL)? 'email': 'username';

        $credentials = [
            $loginType => $login,
            'password' => $request->get('password'),
            //'status' => User::STATUS_ACTIVE
        ];

        if($scope == 'publisher'){
            $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;
            $credentials['role_id'] = $publisherRoleId;
        }

        if($scope == 'admin'){
            $publisherRoleId = Role::firstOrCreate(['name' => User::ADMIN_ROLE])->id;
            $credentials['role_id'] = $publisherRoleId;
        }

        if (!Auth::validate($credentials)){
            return response()->json(['code'=> 401, 'message'=>__('auth.unauthorized')], 401);
        }

        $user = Auth::getLastAttempted();

        if($user->status == User::STATUS_INACTIVE) {

            if (!$user->email_verified_at){
                auth()->emailVerification($user, $scope);
                return response()->json(['code'=> 'auth.email_verification_link_sent', 'message'=>__('auth.email_verification_link_sent')], 401);
            }

            return response()->json(['code'=> 'auth.inactive_account', 'message'=>__('auth.inactive_account')], 401);
        }

        if ($_2fa = $user->_2fa){
            $errors = [];
            $_2faResult = $this->_2faService->check2FA($user, ['ip' => $request->ip()]);

            if (($_2fa->app_status && !$_2faResult['app']) || ($_2fa->email_status && !$_2faResult['email'])){
                $errors['app'] = $_2fa->app_status? 'Please verify app 2FA' : null;
                $errors['email'] = $_2fa->email_status? 'Please verify email 2FA' : null;
            }

            if (!empty($errors)){
                $authKeyModel = new AuthKey();
                $authKeyModel->auth_key = sha1('login.2fa.require.' . $user->id);
                $authKeyModel->user_id = $user->id;
                $authKeyModel->save();
//                Cache::put($authKey, $user->id, 24 * 60 * 60);

                return response()->json([
                    'message' => 'Please verify 2FA',
                    'code' => '2fa.require',
                    'errors' => $errors,
                    'auth_key' => $authKeyModel->auth_key
                ], 403);
            }
        }

        $result['profile'] = UserResource::make($user->append('role_name'));
        $result['token'] =  $user->createToken('access_token')->accessToken;
        return response()->json($result);
    }

    public function sendMagicLogin(Request $request, $scope = 'user')
    {
        $request->validate([
            'login' => 'required|string',
            'captcha' => 'required|captcha_api:' . request('captcha_key') . ',math'
        ]);

        $login = $request->get('login');
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL)? 'email': 'username';

        $userQuery = User::where($loginType, $login);

        if($scope == 'publisher'){
            $roleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;
            $userQuery->where('role_id', $roleId);
        }

        if($scope == 'admin'){
            $roleId = Role::firstOrCreate(['name' => User::ADMIN_ROLE])->id;
            $userQuery->where('role_id', $roleId);
        }

        $user = $userQuery->first();

        if (!$user){
            return response()->json([
                'login' => $login,
                'message' => __('auth.magic_link_sent'),
            ]);
        }

        if($user->status == User::STATUS_INACTIVE) {

            if (!$user->email_verified_at){
                auth()->emailVerification($user, $scope);
                return response()->json(['code'=> 'auth.email_verification_link_sent', 'message'=>__('auth.email_verification_link_sent')], 401);
            }

            return response()->json(['code'=> 'auth.inactive_account', 'message'=>__('auth.inactive_account')], 401);
        }

        $token = sha1($user->id . time());

        $magicLogin = new MagicLogin();
        $magicLogin->email = $user->email;
        $magicLogin->token = $token;
        $magicLogin->expired_at = Carbon::now()->addMinutes(45);
        $magicLogin->save();


        if ($scope == 'publisher'){
            $link = config('general.PUBLISHER_MAGIC_LOGIN_LINK') . $token;
        }elseif ($scope == 'admin'){
            $link = config('general.ADMIN_MAGIC_LOGIN_LINK') . $token;
        }else{
            $link = config('general.MWA_MAGIC_LOGIN_LINK') . $token;
        }

        Mail::to($user->email)
            ->queue(new MagicLoginMail($link));

        return response()->json([
            'login' => $login,
            'message' => __('auth.magic_link_sent'),
        ]);
    }

    public function verifyMagicLogin(Request $request, $token)
    {
        $magicLogin = MagicLogin::where('token', $token)
            ->where('expired_at', '>', Carbon::now())
            ->firstOrFail();

        $user = User::where('email', $magicLogin->email)->firstOrFail();

        if($user->status == User::STATUS_INACTIVE) {
            return response()->json(['code'=>401, 'message'=>__('auth.inactive_account')], 401);
        }

        if ($_2fa = $user->_2fa){
            $errors = [];
            $_2faResult = $this->_2faService->check2FA($user, ['ip' => $request->ip()]);

            if (($_2fa->app_status && !$_2faResult['app']) || ($_2fa->email_status && !$_2faResult['email'])){
                $errors['app'] = $_2fa->app_status? 'Please verify app 2FA' : null;
                $errors['email'] = $_2fa->email_status? 'Please verify email 2FA' : null;
            }

            if (!empty($errors)){
                $authKeyModel = new AuthKey();
                $authKeyModel->auth_key = sha1('login.2fa.require.' . $user->id);
                $authKeyModel->user_id = $user->id;
                $authKeyModel->save();
//                $authKey = sha1('login.2fa.require.' . $user->id);
//                Cache::put($authKey, $user->id, 24 * 60 * 60);

                return response()->json([
                    'message' => 'Please verify 2FA',
                    'code' => '2fa.require',
                    'errors' => $errors,
                    'auth_key' => $authKeyModel->auth_key
                ], 403);
            }
        }

        $result['profile'] = UserResource::make($user->append('role_name'));
        $result['token'] =  $user->createToken('access_token')->accessToken;
        return response()->json($result);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse [string] message
     */
    public function logout(Request $request)
    {
        if($request->user()->token()){
            $request->user()->token()->revoke();
        }

        return response()->json([
            'message' => __('auth.logged_out_successfully')
        ]);

    }

    public function send_password_reset_link(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'captcha' => 'required|captcha_api:' . request('captcha_key') . ',math'
        ]);

        $scope = $request->get('scope');

        $userQuery = User::where("email", $request->get("email"));

        if ($scope == 'admin'){
            $roleId = Role::firstOrCreate(['name' => User::ADMIN_ROLE])->id;
            $userQuery->where('role_id', $roleId);
        }

        $user = $userQuery->first();

        abort_unless($user, 404, 'The email you entered does not exist.');

        $reset_password = new PasswordReset();

        $token = sha1($user->id . time());

        $reset_password->email = $user->email;
        $reset_password->token = $token;
        $reset_password->save();

        if ($scope == 'publisher'){
            $link = config('general.PUBLISHER_PASSWORD_RESET_URL') . $token;
        }elseif ($scope == 'admin'){
            $link = config('general.ADMIN_PASSWORD_RESET_URL') . $token;
        }else{
            $link = config('general.MWA_PASSWORD_RESET_URL') . $token;
        }

        Mail::to($user->email)
            ->queue(new PasswordResetMail($link));

        return response()->json([
            'status' => 'ok',
            'message' => __('auth.password_reset_link_sent'),
        ]);
    }

    public function verify_password_reset_token($token)
    {
        PasswordReset::where('token', $token)
            ->where('created_at', '>', Carbon::now()->subDays(1))
            ->firstOrFail();

        return response()->json(['status' => 'ok']);
    }

    public function reset_password(\App\Http\Requests\PasswordReset $request)
    {
        $password_reset = PasswordReset::where('token', $request->get('token'))
            ->where('created_at', '>', Carbon::now()->subDays(1))
            ->firstOrFail();

        $user = User::where('email', $password_reset->email)->firstOrFail();

        $user->password = Hash::make($request->get('password'));

        $user->save();

        DB::table('password_resets')->where([
            'email' => $password_reset->email,
            'token' => $password_reset->token,
        ])->delete();

        return response()->json(['status' => 'ok', 'message' => __('auth.password_changed_successfully')]);
    }
}
