<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    protected $fillable = ['key', 'value'];

    protected $table = 'user_meta';

    const NEW_ETH_ADDRESS_KEY = 'new_eth_address';
    const NEW_ETH_ADDRESS_VERIFICATION_CODE_KEY = 'new_eth_address_verification_code';
    const REQUESTED_CHANNEL_NAME = 'requested_channel_name';
    const PUBLISHER_REQUEST_STATUS = 'publisher_request_status';
    const PAYMENT_DETAILS = 'payment_details';
    const IDENTIFICATION_DETAILS = 'identification_details';
    const CustomFeedSetting = 'custom_feed_setting';
    const MonetizeReferralPointsIsActive = 'monetize_referral_points_is_active';
    const ChannelAutoImportIsActive = 'channel_auto_import_is_active';
    const UserNameChangedAt = 'username_changed_at';
    const LoginTypes = 'login_types';

    const KEYS_WITH_JSON_VALUE = [
        self::PAYMENT_DETAILS,
        self::IDENTIFICATION_DETAILS,
        self::CustomFeedSetting,
        self::LoginTypes,
    ];


    // Relations
    public function user(){
        return $this->belongsTo('App\Models\User');
    }


    // Attributes
    public function getValueAttribute($value){
        if (in_array($this->key, self::KEYS_WITH_JSON_VALUE)){
            return json_decode($value, true);
        }
        return $value;
    }
}
