<?php


namespace App\Services;


use App\Mail\_2FACodeMail;
use App\Mail\PublisherVerificationMail;
use App\Models\_2FA;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class _2FAService
{
    public function sendEmail2FACode($user)
    {
        $cacheKey = sha1('2fa-email' . $user->id);
        $code = rand(100000,999999);

        Cache::put($cacheKey, $code, 5 * 60);

        // Send code to user email
        Mail::to($user->email)
            ->queue(new _2FACodeMail($code));

        return true;
    }


    // Verify
    public function verify($user, $data)
    {
        $result = [];

        if (!empty($data['email'])){
            $result['email'] = $this->verifyEmail2FA($user, $data['email']);
        }

        if (!empty($data['app'])){
            $result['app'] = $this->verifyApp2FA($user, $data['app']);
        }

        return $result;
    }

    private function verifyEmail2FA($user, $code)
    {
        $cacheKey = sha1('2fa-email' . $user->id);

        if (!($_2fa = $user->_2fa)){
            $_2fa = new _2FA();
            $_2fa->user_id = $user->id;
        }

        if (Cache::get($cacheKey) == $code){
            $_2fa->email_verified_at = Carbon::now();
            $_2fa->save();
            return true;
        }

        return false;
    }

    private function verifyApp2FA($user, $secret)
    {
        $google2fa = new Google2FA();

        if (!($_2fa = $user->_2fa)){
            $_2fa = new _2FA();
            $_2fa->user_id = $user->id;
        }

        if ($_2fa->app_secret && $google2fa->verifyKey($_2fa->app_secret, $secret)){
            $_2fa->app_verified_at = Carbon::now();
            $_2fa->save();
            return true;
        }

        return false;
    }

    // Checking 2FA
    public function check2FA($user, $types, $minutes = 1)
    {
        $_2fa = $user->_2fa;
        $result = [];

        foreach ($types as $type){
            if ($_2fa && $_2fa->{"{$type}_verified_at"} > Carbon::now()->subMinutes($minutes)) {
                $result[$type] = true;
            }else{
                $result[$type] = false;
            }
        }

        return $result;
    }
}