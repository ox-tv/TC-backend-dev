<?php

namespace App\Http\Controllers;

use App\Libraries\IdenfyClient;
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

        $response = $client->ceateToken($user->id, [
            'first_name' => 'ali',
            'last_name' => 'helali',
        ]);

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

        return response()->json(['qrcode' => $qrcodeImage, 'url' => $url]);
    }


}
