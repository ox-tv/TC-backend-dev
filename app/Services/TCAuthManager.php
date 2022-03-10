<?php

namespace App\Services;

use App\Mail\PublisherVerificationMail;
use App\Mail\VerificationMail;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Mail;

class TCAuthManager extends AuthManager
{
    public function emailVerification($user, $scope)
    {
        $token = sha1($user->id . time());
        $user->verification_code = $token;
        $user->save();

        if ($scope == 'publisher'){
            $link = config('general.PUBLISHER_EMAIL_VERIFICATION_URL') . $token;
            Mail::to($user->email)
                ->queue(new PublisherVerificationMail($link));
        }else{
            $link = config('general.MWA_EMAIL_VERIFICATION_URL') . $token;
            Mail::to($user->email)
                ->queue(new VerificationMail($link));
        }

        return true;
    }
}