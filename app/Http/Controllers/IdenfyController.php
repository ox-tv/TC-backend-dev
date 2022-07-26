<?php

namespace App\Http\Controllers;

use App\Libraries\IdenfyClient;
use Illuminate\Http\Request;

class IdenfyController extends Controller
{
    public function webHookHandler(Request $request)
    {

    }

    public function getRedirectUrl(Request $request)
    {
        $client = new IdenfyClient();

        $response = $client->ceateToken('10000',[
            'first_name' => 'ali',
            'last_name' => 'helali',
        ]);

        dd($response);
    }

}
