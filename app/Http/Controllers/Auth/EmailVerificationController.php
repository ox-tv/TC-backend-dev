<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\_2FA\_2FAResource;
use App\Http\Resources\User\UserResource;
use App\Mail\_2FACodeMail;
use App\Models\_2FA;
use App\Models\User;
use App\Services\_2FAService;
use App\Services\EmailVerificationService;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class EmailVerificationController extends Controller
{
    private $EmailVerificationService;

    public function __construct(EmailVerificationService $EmailVerificationService)
    {
        $this->EmailVerificationService = $EmailVerificationService;
    }

    public function sendCode(Request $request)
    {
        // Detect user
        if (auth('api')->check()){

            $user = auth('api')->user();

        }else if($request->header('tc-auth-key')) {

            $request->merge(['auth-key' => $request->header('tc-auth-key')]);
            $request->validate([
                'auth-key' => [
                    'sometimes',
                    function ($attribute, $value, $fail) {
                        if ($value && !Cache::has($value)) {
                            $fail('The '.$attribute.' is invalid.');
                        }
                    },
                ],
            ]);

            $userId = Cache::get($request->get('auth-key'));
            $user = User::where('id', $userId)->firstOrFail();

        }else{
            return response()->json([
                "message" => "Unauthenticated."
            ], 401);
        }

        $this->EmailVerificationService->sendCode($user);

        return response()->json(['status' => 'ok']);
    }

    public function verify(Request $request)
    {
        // Detect user
        if (auth('api')->check()){

            $user = auth('api')->user();

        }else if($request->header('tc-auth-key')) {

            $request->merge(['auth-key' => $request->header('tc-auth-key')]);
            $request->validate([
                'auth-key' => [
                    'sometimes',
                    function ($attribute, $value, $fail) {
                        if ($value && !Cache::has($value)) {
                            $fail('The '.$attribute.' is invalid.');
                        }
                    },
                ],
            ]);

            $userId = Cache::get($request->get('auth-key'));
            $user = User::where('id', $userId)->firstOrFail();

        }else{
            return response()->json([
                "message" => "Unauthenticated."
            ], 401);
        }

        // Checking Code
        $request->validate([
            'email_verification_code' => ['required', 'numeric', 'digits:6'],
        ]);

        $this->EmailVerificationService->verify($user, $request->get('email_verification_code'));

        return response()->json(['status' => 'ok']);
    }

}
