<?php


namespace App\Services;


use App\Mail\_2FACodeMail;
use App\Mail\PublisherVerificationMail;
use App\Models\_2FA;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class _2FAService
{
    public function sendEmail2FACode($user)
    {
        $model = new VerificationCode();
        $model->code = rand(100000,999999);
        $model->user_id = $user->id;
        $model->expired_at = Carbon::now()->addMinutes(5);
        $model->save();

        /*$cacheKey = sha1('2fa-email' . $user->id);
        $code = rand(100000,999999);

        Cache::put($cacheKey, $code, 5 * 60);*/

        // Send code to user email
        Mail::to($user->email)
            ->queue(new _2FACodeMail($model->code));

        return true;
    }


    // Verify
    public function verify($user, $data)
    {
        $result = [
            'app' => false,
            'email' => false,
        ];

        $_2fa = $user->_2fa;

        if (!$_2fa){
            $_2fa = new _2FA();
            $_2fa->user_id = $user->id;
        }

        if (!empty($data['email']) && $this->verifyEmail2FA($user, $data['email'])){
            $result['email'] = true;
            $_2fa->email_verified_at = Carbon::now();
            $_2fa->ip = request()->ip();
        }

        if (!empty($data['app']) && $this->verifyApp2FA($user, $data['app'])){
            $result['app'] = true;
            $_2fa->app_verified_at = Carbon::now();
            $_2fa->ip = request()->ip();
        }

        $_2fa->save();

        return $result;
    }

    private function verifyEmail2FA($user, $code)
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

        /*$cacheKey = sha1('2fa-email' . $user->id);

        if (Cache::get($cacheKey) == $code){
            return true;
        }*/

        return true;
    }

    private function verifyApp2FA($user, $secret)
    {
        $google2fa = new Google2FA();
        $_2fa = $user->_2fa;

        if ($_2fa->app_secret && $google2fa->verifyKey($_2fa->app_secret, $secret)){
            return true;
        }

        return false;
    }

    // Checking 2FA
    public function check2FA($user, $options = [])
    {
        $ip = $options['ip'] ?? null;
        $minutes = $options['minutes'] ?? 1;

        $_2fa = $user->_2fa;
        $result = [
            'app' => false,
            'email' => false,
        ];

        if (!$_2fa /*|| ($ip && $_2fa->ip != $ip)*/){
            return $result;
        }

        if ($_2fa->app_verified_at > Carbon::now()->subMinutes($minutes)){
            $result['app'] = true;
        }

        if ($_2fa->email_verified_at > Carbon::now()->subMinutes($minutes)){
            $result['email'] = true;
        }

        return $result;
    }
}