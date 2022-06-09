<?php

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
use BaconQrCode\Encoder\QrCode;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Crypt;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class _2FAController extends Controller
{
    public function enable(Request $request, $type)
    {
        switch ($type){
            case 'google':{
                return $this->enableGoogle2FA();
            }
            default:{
                throw new \Exception('Invalid 2fa type', '500');
            }
        }
    }

    // enables
    private function enableGoogle2FA()
    {
        $google2fa = new Google2FA();

        $user = auth('api')->user();

        $user->google2fa_secret = $google2fa->generateSecretKey();
        $user->save();

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name') ,
            $user->email,
            $user->google2fa_secret
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new ImagickImageBackEnd()
            )
        );

        $qrcode_image = base64_encode($writer->writeString($qrCodeUrl));

        return response()->json(['qrcode' => $qrcode_image]);
    }

    public function disable()
    {
        $user = auth('api')->user();

        $user->google2fa_secret = null;
        $user->save();

        response()->json(['status' => 'ok']);
    }

    public function verifyGoogle2FA(Request $request)
    {
        $secret = $request->get('secret');

        $user = User::where('email', $request->get('email'))->firstOrFail();

        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->google2fa_secret, $secret);

        return $valid? response()->json(['status' => 'ok']) : response()->json(['message' => 'secret code is invalid'], 401);
    }
}
