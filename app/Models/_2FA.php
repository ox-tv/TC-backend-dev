<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class _2FA extends Model
{
    protected $fillable = ['user_id', 'app_status', 'app_secret', 'email_status'];

    protected $table = '2fa';

    const APP_STATUS_DISABLE = 0;
    const APP_STATUS_GOOGLE = 1;

    const EMAIL_STATUS_DISABLE = 0;
    const EMAIL_STATUS_ENABLE = 1;


    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

}
