<?php


namespace App\Services;


use App\Mail\_2FACodeMail;
use App\Mail\EmailVerificationCodeMail;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class EmailVerificationService
{
    public function sendCode($user)
    {
        $model = new VerificationCode();
        $model->code = rand(100000,999999);
        $model->user_id = $user->id;
        $model->expired_at = Carbon::now()->addMinutes(5);
        $model->save();

        /*$cacheKey = sha1('email-verification-code-' . $user->id);
        $code = rand(100000,999999);

        Cache::put($cacheKey, $code, 5 * 60);*/

        // Send code to user email
        Mail::to($user->email)
            ->queue(new EmailVerificationCodeMail($model->code));

        return true;
    }

    public function verify($user, $code)
    {
        $model = VerificationCode::where('user_id', $user->id)
            ->where('expired_at', '>=', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$model || $model->code != $code){
            return false;
        }

        $model->verified_at = Carbon::now();
        $model->save();


//        $codeCacheKey = sha1('email-verification-code-' . $user->id);

//        if (Cache::get($codeCacheKey) != $code){
//            return false;
//        }

//        $verifiedCacheKey = sha1('email-verified-' . $user->id);
//        Cache::put($verifiedCacheKey, true, 60);

        return true;
    }

    public function check($user)
    {
        $model = VerificationCode::where('user_id', $user->id)
            ->where('verified_at', '>=', Carbon::now()->subMinutes(2))->first();

        return (bool)$model;

        /*$verifiedCacheKey = sha1('email-verified-' . $user->id);

        if (Cache::has($verifiedCacheKey)){
            return true;
        }

        return false;*/
    }
}