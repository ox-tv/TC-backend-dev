<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\_2FA;
use App\Models\User;
use App\Services\_2FAService;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;

class _2FAController extends Controller
{
    private $_2faService;

    public function __construct(_2FAService $_2faService)
    {
        $this->_2faService = $_2faService;
    }

    // Verify
    public function verify(Request $request)
    {
        if ($request->header('tc-auth-key')){
            $request->merge(['auth-key' => $request->header('tc-auth-key')]);
        }
        $request->validate([
            'auth-key' => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if (!Cache::has($value)) {
                        $fail('The '.$attribute.' is invalid.');
                    }
                },
            ],
            'email_2fa_code' => ['sometimes'],
            'app_2fa_secret' => ['sometimes'],
        ]);
        if ($request->get('auth-key')){
            $userId = Cache::get($request->get('auth-key'));
            $user = User::where('id', $userId)->firstOrFail();
        }else if (auth('api')->check()){
            $user = auth('api')->user();
        }else{
            return response()->json([
                "message" => "Unauthenticated."
            ], 401);
        }

        $data = [];
        $mapKeys = [
            'email_2fa_code' => 'email',
            'app_2fa_secret' => 'app',
        ];
        foreach ($request->only(['email_2fa_code', 'app_2fa_secret']) as $k => $v){
            $data[$mapKeys[$k]] = $v;
        }

        $result = $this->_2faService->verify($user, $data);

        $errors = [];
        if (isset($result['app']) && !$result['app']){
            $errors['app'] = 'Code is not correct';
        }

        if (isset($result['email']) && !$result['email']){
            $errors['email'] = 'Code is not correct';
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'verify failed.',
                'code' => '2fa.verify.fail',
                'errors' => $errors
            ], 422);
        }

        return response()->json(['status' => 'ok']);
    }

    // Email 2FA
    public function sendEmail2FACode()
    {
        $user = auth('api')->user();

        $this->_2faService->sendEmail2FACode($user);

        return response()->json(['status' => 'ok']);
    }

    public function enableEmail2FA(Request $request)
    {
        $user = auth('api')->user();

        $result = $this->_2faService->check2FA($user, ['email']);

        if (!$result['email']){
            return response()->json([
                'message' => 'Please verify 2FA.',
                'code' => '2fa.require',
                'errors' => [
                    'email' => 'Please verify email 2FA'
                ]
            ], 403);
        }

        $_2fa = $user->_2fa;
        $_2fa->email_status = _2FA::EMAIL_STATUS_ENABLE;
        $_2fa->save();

        return response()->json(['status' => 'ok']);
    }

    public function disableEmail2FA(Request $request)
    {
        $user = auth('api')->user();
        $_2fa = $user->_2fa;

        $_2fa->email_status = _2FA::EMAIL_STATUS_DISABLE;
        $_2fa->email_verified_at = null;
        $_2fa->save();

        return response()->json(['status' => 'ok']);
    }

    // GOOGLE 2FA
    public function getApp2FAQrCode()
    {
        $user = auth('api')->user();
        $google2FAClient = new Google2FA();

        if (!($_2fa = $user->_2fa)){
            $_2fa = new _2FA();
            $_2fa->user_id = $user->id;
        }else if ($_2fa->app_status == _2FA::APP_STATUS_ENABLE && $_2fa->app_type == _2FA::APP_TYPE_GOOGLE){
            return response()->json([
                'message' => 'Google 2FA has already been enabled',
                'code' => '2fa.google.already_enabled',
            ], 400);
        }

        $_2fa->app_status = _2FA::APP_STATUS_DISABLE;
        $_2fa->app_type = _2FA::APP_TYPE_GOOGLE;
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

    public function enableGoogle2FA(Request $request)
    {
        $user = auth('api')->user();

        $result = $this->_2faService->check2FA($user, ['app']);

        if (!$result['app']){
            return response()->json([
                'message' => 'Please verify 2FA.',
                'code' => '2fa.require',
                'errors' => [
                    'app' => 'Please verify google 2FA'
                ]
            ], 403);
        }

        $_2fa = $user->_2fa;
        $_2fa->app_status = _2FA::APP_STATUS_ENABLE;
        $_2fa->save();

        return response()->json(['status' => 'ok']);
    }

    public function disableGoogle2FA(Request $request)
    {
        $user = auth('api')->user();
        $_2fa = $user->_2fa;

        $_2fa->app_status = _2FA::APP_STATUS_DISABLE;
        $_2fa->app_verified_at = null;
        $_2fa->app_type = null;
        $_2fa->app_secret = null;
        $_2fa->save();

        return response()->json(['status' => 'ok']);
    }
}
