<?php

namespace App\Http\Controllers\Auth;

use Amir\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\User\UserItem;
use App\Mail\PasswordResetMail;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;

class LoginController extends Controller
{

    public function login(LoginRequest $request, $scope = 'user')
    {
        $login = $request->get('email')?:$request->get('login');
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL)? 'email': 'username';

        if(Auth::validate([$loginType => $login, 'password' => $request->get('password')])){
            $user = Auth::getLastAttempted();
            if($user->status == User::STATUS_INACTIVE) {
                return response()->json(['code'=>401, 'message'=>__('auth.inactive_account')], 401);
            }
        }

        $credentials = [
            $loginType => $login,
            'password' => $request->get('password'),
            'status' => User::STATUS_ACTIVE
        ];

        if($scope == 'publisher'){
            $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;
            $credentials['role_id'] = $publisherRoleId;
        }

        if($scope == 'admin'){
            $publisherRoleId = Role::firstOrCreate(['name' => User::ADMIN_ROLE])->id;
            $credentials['role_id'] = $publisherRoleId;
        }

        $attempt = Auth::attempt($credentials);

        if($attempt){
            $user = Auth::user();
            $result['profile'] = UserItem::make($user->load('role'));
            $result['token'] =  $user->createToken('access_token')->accessToken;
            return response()->json($result, '200');
        }

        return response()->json(['code'=>401, 'message'=>__('auth.unauthorized')], 401);
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
        $user = User::where("email", $request->get("email"))->first();

        abort_unless($user, 404, 'The email you entered does not exist.');

        $reset_password = new PasswordReset();

        $token = sha1($user->id . time());

        $reset_password->email = $user->email;
        $reset_password->token = $token;
        $reset_password->save();

        if ($request->get('scope') == 'publisher'){
            $link = config('general.PUBLISHER_PASSWORD_RESET_URL') . $token;
        }elseif ($request->get('scope') == 'admin'){
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
