<?php

namespace App\Http\Controllers;

use App\Libraries\IdenfyClient;
use App\Models\PaymentDetails;
use App\Models\User;
use App\Models\UserMeta;
use App\Repository\Eloquent\TagRepository;
use App\Services\_2FAService;
use App\Services\EmailVerificationService;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
}
