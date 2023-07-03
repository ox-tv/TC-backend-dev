<?php

namespace App\Services;


use App\Http\Resources\Ad\AdDiscountResource;
use App\Mail\_2FACodeMail;
use App\Mail\PublisherVerificationMail;
use App\Models\_2FA;
use App\Models\AdDiscount;
use App\Models\Option;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

class AdManagerService
{
    public function getPricePerSlot($tier, $date)
    {
        // Get prices
        $option = Option::get(Option::AD_TIERS_DATA);
        if (!$option || !is_json_string($option->value)){
            return 0;
        }

        $optionArray = json_decode($option->value, true);
        $tierKey = array_search($tier, array_column($optionArray, 'tier'));
        $tierData = $optionArray[$tierKey];

        return $tierData['cpm'] / 7 / 5;
    }
}