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

    public function user(){
        return $this->belongsTo('App\Models\User');
    }
}
