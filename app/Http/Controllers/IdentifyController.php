<?php

namespace App\Http\Controllers;

use App\Libraries\IdenfyClient;
use App\Models\User;
use App\Models\UserMeta;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IdentifyController extends Controller
{
    public function webHookHandler(Request $request)
    {
        $data = $request->all();

        if (empty($data['clientId'])){
            return response()->json(['message' => 'client id is not detected']);
        }

        $user = User::find($data['clientId']);

        if (!$user){
            return response()->json(['message' => 'client id is not detected in our system']);
        }

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::IDENTIFICATION_DETAILS],
            ['value' => json_encode($data)]
        );

        if ($data['status']['overall'] == 'APPROVED'){
            $user->identity_verified_at = Carbon::now();
            $user->save();
        }

        return response()->json(['status' => 'ok']);
    }

    public function getWebUiUrl(Request $request)
    {
        $client = new IdenfyClient();
        $user = auth('api')->user();

        $response = $client->ceateToken($user->id, [
            'webhook_url' => route('idenfy.webhook-handler')
        ]);

        if (!$response['success']){
            return response()->json(['message' => $response['message']], 400);
        }

        $url = $client->createWebUiUrl($response['data']['authToken']);

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new ImagickImageBackEnd()
            )
        );

        $qrcodeImage = 'data:image/png;base64,' . base64_encode($writer->writeString($url));

        return response()->json(['url' => $url, 'qrcode' => $qrcodeImage]);
    }

    public function storePaymentDetails(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'street_address' => ['required'],
            'street_number' => ['required'],
            'postal_code' => ['required'],
            'city' => ['required'],
            'country' => ['required'],
            'company_name' => ['nullable'],
            'vat_number' => ['nullable'],
            'eth_address' => 'required|regex:/^0x[a-fA-F0-9]{40}$/',
        ]);

        $user = auth('api')->user();

        $_2fa = $user->_2fa;

        if ($_2fa && ($_2fa->app_status || $_2fa->email_status)){
            // 2FA verification
            $errors = [];
            $_2faResult = $this->_2faService->check2FA($user, ['ip' => $request->ip()]);

            if (($_2fa->app_status && !$_2faResult['app']) || ($_2fa->email_status && !$_2faResult['email'])){
                $errors['app'] = $_2fa->app_status? 'Please verify app 2FA' : null;
                $errors['email'] = $_2fa->email_status? 'Please verify email 2FA' : null;
            }

            if (!empty($errors)){
                return response()->json([
                    'message' => 'Please verify 2FA',
                    'code' => '2fa.require',
                    'errors' => $errors
                ], 403);
            }

        }else if (!$this->EmailVerificationService->check($user)){
            // Email Verification
            $this->EmailVerificationService->sendCode($user);
            return response()->json([
                'message' => 'Please pass email verification',
                'code' => 'email_verification.require',
            ], 403);
        }

        $user->eth_address = $request->get('eth_address');
        $user->save();

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::PAYMENT_DETAILS],
            ['value' => json_encode($request->except(['eth_address']))]
        );

        return response()->json(['status' => 'ok']);
    }
}
