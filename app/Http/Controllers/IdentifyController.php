<?php

namespace App\Http\Controllers;

use App\Libraries\IdenfyClient;
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

    }

    public function getWebUiUrl(Request $request)
    {
        $client = new IdenfyClient();
        $user = auth('api')->user();

        $response = $client->ceateToken($user->id);

        if (!$response['success']){
            return response()->json(['message' => $response['data']['message']], 400);
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
        $validated = $request->validate([
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
            ['value' => json_encode($validated)]
        );

        return response()->json(['status' => 'ok']);
    }
}
