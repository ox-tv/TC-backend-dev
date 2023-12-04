<?php

namespace App\Http\Controllers\Auth;

use Amir\Permission\Models\Role;
use App\Events\UserVerified;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Elliptic\EC;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use kornrunner\Keccak;

class Web3LoginController extends Controller
{

    public function getMessageForSign()
    {
        $nonce = Str::random();
        $key = sha1($nonce);

        $message = "By logging in to my Today's Crypto account I certify that I have read, understood, and accepted the Terms of Service and Privacy Policy.\n\nNonce: " . $nonce;

        cache()->put($key, $message, Carbon::now()->addMinutes(10));

        return response()->json(['key' => $key, 'message' => $message]);
    }

    public function loginWithWallet(Request $request, $scope = 'user')
    {
        $request->merge([
            'referral_code' => strtoupper($request->get('referral_code')),
        ]);

        $request->validate([
            'address' => ['required', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'referral_code' => ['nullable', 'string', Rule::exists('users', 'referral_code')],
            'key' => ['required'],
            'signature' => [
                'required',
                function ($attribute, $signature, $fail) {
                    if (!cache()->has(request()->get('key'))){
                        throw new Exception();
                    }

                    $message = cache()->pull(request()->get('key'));
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

        $userQuery = User::where('auth_wallet', $request->get('address'));


        if($scope == 'publisher'){
            $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;
            $userQuery->where('role_id', $publisherRoleId);
        }

        if($scope == 'admin'){
            $publisherRoleId = Role::firstOrCreate(['name' => User::ADMIN_ROLE])->id;
            $userQuery->where('role_id', $publisherRoleId);
        }

        if (!($user = $userQuery->first())){
            // new user
            $user = new User();
            $user->auth_wallet = $request->get('address');
            $user->email_verified_at = Carbon::now();
            $user->status = User::STATUS_ACTIVE;

            do{
                $referral_code = strtoupper(Str::random(6));
            }while(User::where('referral_code', $referral_code)->exists());

            $user->referral_code = $referral_code;

            if($request->get('referral_code')){
                $referrer = User::where('referral_code', $request->get('referral_code'))->first();
                $user->referrer_id = $referrer->id;
            }

            $user->registration_ip = getClientIP($request);

            $user->save();

            event(new UserVerified($user));
        }


        if($scope == 'publisher'){
            $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;
            $credentials['role_id'] = $publisherRoleId;
        }

        if($scope == 'admin'){
            $publisherRoleId = Role::firstOrCreate(['name' => User::ADMIN_ROLE])->id;
            $credentials['role_id'] = $publisherRoleId;
        }

        if($user->status == User::STATUS_INACTIVE) {

            if (!$user->email_verified_at){
                auth()->emailVerification($user, $scope);
                return response()->json(['code'=> 'auth.email_verification_link_sent', 'message'=>__('auth.email_verification_link_sent')], 401);
            }

            return response()->json(['code'=> 'auth.inactive_account', 'message'=>__('auth.inactive_account')], 401);
        }

        Log::channel('coinmarketcap')->info(getClientIP($request));

        $result['profile'] = UserResource::make($user->append('role_name'));
        $result['token'] =  $user->createToken('access_token')->accessToken;
        return response()->json($result);
    }
}
