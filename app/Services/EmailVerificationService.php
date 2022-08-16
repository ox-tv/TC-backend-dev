<?php


namespace App\Services;


use App\Mail\_2FACodeMail;
use App\Mail\EmailVerificationCodeMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class EmailVerificationService
{
    public function sendCode($user)
    {
        $cacheKey = sha1('email-verification-code-' . $user->id);
        $code = rand(100000,999999);

        Cache::put($cacheKey, $code, 5 * 60);

        // Send code to user email
        Mail::to($user->email)
            ->queue(new EmailVerificationCodeMail($code));

        return true;
    }

    public function verify($user, $code)
    {
        $codeCacheKey = sha1('email-verification-code-' . $user->id);

        if (Cache::get($codeCacheKey) != $code){
            return false;
        }

        $verifiedCacheKey = sha1('email-verified-' . $user->id);
        Cache::put($verifiedCacheKey, true, 60);

        return true;
    }

    public function check($user)
    {
        $verifiedCacheKey = sha1('email-verified-' . $user->id);

        if (Cache::has($verifiedCacheKey)){
            return true;
        }

        return false;
    }
}