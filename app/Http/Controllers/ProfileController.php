<?php

namespace App\Http\Controllers;


use Amir\Permission\Models\Role;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /*
     *
     * Get Data
     *
     */
    public function getMembershipData()
    {
        $user = auth('api')->user();

        return response()->json([
            'has_stripe_subscription' => $user->subscribed('default'),
            'is_hero' => $user->is_hero,
            'due_at' => $user->hero_due_at,
        ]);
    }

    public function getSecurityData()
    {
        $user = auth('api')->user();

        $loginTypeWalletStatus = ($meta = $user->meta()->where('key', UserMeta::LoginTypeWallet)->first())? (bool) $meta->value : (bool) $user->auth_wallet;
        $LoginTypeCredentialsStatus = ($meta = $user->meta()->where('key', UserMeta::LoginTypeCredentials)->first())? (bool) $meta->value : (bool) $user->email;

        return response()->json([
            'login_type_wallet' => $loginTypeWalletStatus,
            'login_type_credentials' => $LoginTypeCredentialsStatus,
        ]);
    }

    /*
     *
     * Set Data
     *
     */
    public function setLoginType(Request $request)
    {
        $user = auth('api')->user();
        $loginTypeWalletStatus = ($meta = $user->meta()->where('key', UserMeta::LoginTypeWallet)->first())? (bool) $meta->value : (bool) $user->auth_wallet;
        $LoginTypeCredentialsStatus = ($meta = $user->meta()->where('key', UserMeta::LoginTypeCredentials)->first())? (bool) $meta->value : (bool) $user->email;

        $request->validate([
            'login_type_wallet' => [
                function($attribute, $value, $fail) use($user){
                    if ($value === true && !$user->auth_wallet) {
                        $fail('You do not have a wallet.');
                    }
                },
                Rule::requiredIf(request()->get('login_type_credentials') === false || (!$LoginTypeCredentialsStatus && !request()->get('login_type_credentials') && !request()->get('login_type_wallet'))), 'boolean'
            ],
            'login_type_credentials' => [
                Rule::requiredIf(request()->get('login_type_wallet') === false || (!$loginTypeWalletStatus && !request()->get('login_type_wallet') && !request()->get('login_type_credentials'))), 'boolean'
            ],
            'credentials.email' => [
                function($attribute, $value, $fail) use($user){
                    if (request()->get('login_type_credentials') && !$user->email && !$value) {
                        $fail('The email field is required.');
                    }
                },
                Rule::unique('users', 'email')->where(function($q) {
                    $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;
                    $q->whereNull('role_id')->orWhere('role_id', $publisherRoleId);
                })->whereNotNull('email_verified_at')
            ],
            'credentials.password' => [
                function($attribute, $value, $fail) use($user){
                    if (request()->get('login_type_credentials') && !$user->email && !$value) {
                        $fail('The password is required.');
                    }
                },
                'string', 'min:8'
            ],
        ]);

        if ($request->get('login_type_credentials') && !$user->email){
            $user->email = $request->get('credentials.email');
            $user->password = Hash::make($request->get('credentials.password'));
            $user->save();
        }

        if ($request->get('login_type_wallet') !== null){
            $user->meta()->updateOrCreate(
                ['key' => UserMeta::LoginTypeWallet],
                ['value' => $request->get('login_type_wallet')]
            );
        }

        if ($request->get('login_type_credentials') !== null){
            $user->meta()->updateOrCreate(
                ['key' => UserMeta::LoginTypeCredentials],
                ['value' => $request->get('login_type_credentials')]
            );
        }

        return response()->json(['message' => 'ok']);
    }
}
