<?php

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Mail\PublisherVerificationMail;
use App\Models\_2FA;
use App\Rules\CustomRule;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class _2FAController extends Controller
{
    // Email 2FA
    public function email2FAEnableRequest()
    {
        $user = auth('api')->user();
        $cacheKey = sha1('enable-2fa-email' . $user->id);
        $code = rand(100000,999999);

        Cache::put($cacheKey, $code, 5 * 60);

        // Send code to user email
        Mail::to($user->email)
            ->queue(new PublisherVerificationMail($code));

        return response()->json(['status' => 'ok']);
    }

    public function email2FAEnable(Request $request)
    {
        $user = auth('api')->user();
        $_2fa = $user->_2fa()->firstOrFail();

        $request->validate([
            '_2fa_secret' => ['required', CustomRule::email2FA(sha1('enable-2fa-email' . $user->id))],
        ]);

        $_2fa->email_status = _2FA::EMAIL_STATUS_ENABLE;
        $_2fa->save();

        return response()->json(['status' => 'ok']);
    }

    public function email2FADisableRequest()
    {
        $user = auth('api')->user();
        $cacheKey = sha1('disable-2fa-email' . $user->id);
        $code = rand(100000,999999);

        Cache::put($cacheKey, $code, 5 * 60);

        // Send code to user email
        Mail::to($user->email)
            ->queue(new PublisherVerificationMail($code));

        return response()->json(['status' => 'ok']);
    }

    public function email2FADisable(Request $request)
    {
        $user = auth('api')->user();
        $_2fa = $user->_2fa()->firstOrFail();

        $request->validate([
            '_2fa_secret' => ['required', CustomRule::email2FA(sha1('disable-2fa-email' . $user->id))],
        ]);

        $_2fa->email_status = _2FA::EMAIL_STATUS_DISABLE;
        $_2fa->save();

        return response()->json(['status' => 'ok']);
    }


    // GOOGLE 2FA
    public function google2FAEnableRequest()
    {
        $user = auth('api')->user();
        $_2fa = $user->_2fa;

        $google2FAClient = new Google2FA();

        if (!$_2fa){
            $_2fa = new _2FA();
            $_2fa->user_id = $user->id;
        }else if ($_2fa->app_status == _2FA::APP_STATUS_GOOGLE){
            return response()->json(['message' => 'Google 2FA has already been enabled'], 403);
        }

        $_2fa->app_status = _2FA::APP_STATUS_DISABLE;
        $_2fa->app_secret = $google2FAClient->generateSecretKey();
        $_2fa->save();

        // Create QRCode for Scan on App
        $qrCodeUrl = $google2FAClient->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $_2fa->app_secret
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new ImagickImageBackEnd()
            )
        );

        $qrcode_image = 'data:image/png;base64,' . base64_encode($writer->writeString($qrCodeUrl));

        return response()->json(['qrcode' => $qrcode_image]);
    }

    public function google2FAEnable(Request $request)
    {
        $user = auth('api')->user();
        $_2fa = $user->_2fa()->firstOrFail();

        $request->validate([
            '_2fa_secret' => ['required', CustomRule::google2FA($_2fa->app_secret)],
        ]);

        $_2fa->app_status = _2FA::APP_STATUS_GOOGLE;
        $_2fa->save();

        return response()->json(['status' => 'ok']);
    }

    public function google2FADisable(Request $request)
    {
        $user = auth('api')->user();
        $_2fa = $user->_2fa()->firstOrFail();

        $request->validate([
            '_2fa_secret' => ['required', CustomRule::google2Fa($_2fa->app_secret)],
        ]);

        $_2fa->app_secret = null;
        $_2fa->app_status = _2FA::APP_STATUS_DISABLE;
        $_2fa->save();

        return response()->json(['status' => 'ok']);
    }
}
