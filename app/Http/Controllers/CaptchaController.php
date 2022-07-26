<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Mews\Captcha\Captcha;

class CaptchaController extends Controller
{

    public function get(Captcha $captcha){

        return $captcha->create('math', true);

    }

    public function verify(Request $request)
    {
        $rules = ['captcha' => 'required|captcha_api:' . request('key') . ',math'];
        $validator = validator()->make(request()->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'invalid captcha',
            ], 422);

        }

        return response()->json(["message" => "ok"]);

    }

}
