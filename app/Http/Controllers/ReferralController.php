<?php

namespace App\Http\Controllers;

use App\Models\MonetizePoint;
use App\Models\UserMeta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function setPointsToActive()
    {
        $user = auth('api')->user();
        $channel = $user->channel;

        if(!$channel){
            abort(404, 'channel not found.');
        }

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::MonetizeReferralPointsIsActive],
            ['value' => true]
        );

        MonetizePoint::where('type', MonetizePoint::TYPE_REFERRAL)
            ->where('channel_id', $channel->id)
            ->update(['activated_at' => MonetizePoint::fromDateTime(Carbon::now())]);

        return response()->json(['status' => 'ok']);
    }

}
