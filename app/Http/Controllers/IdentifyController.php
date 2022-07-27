<?php

namespace App\Http\Controllers;

use App\Libraries\IdenfyClient;
use App\Models\User;
use App\Models\UserMeta;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
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
        ]);

        $user = auth('api')->user();

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::PAYMENT_DETAILS],
            ['value' => json_encode($validatedData)]
        );

        return response()->json(['status' => 'ok']);
    }
}
