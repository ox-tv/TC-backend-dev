<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class _2FA extends Model
{
    protected $fillable = ['user_id', 'app_status', 'app_secret', 'email_status'];

    protected $table = '2fa';

    protected $hidden = [
        'app_secret',
    ];

    const APP_STATUS_DISABLE = 0;
    const APP_STATUS_GOOGLE = 1;

    const APP_STATUS_TEXT = [
        self::APP_STATUS_DISABLE => null,
        self::APP_STATUS_GOOGLE => 'google',
    ];

    const EMAIL_STATUS_DISABLE = 0;
    const EMAIL_STATUS_ENABLE = 1;


    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function getAppStatusTextAttribute()
    {
        return array_key_exists($this->app_status, self::APP_STATUS_TEXT)?
            self::APP_STATUS_TEXT[$this->app_status]:
            $this->app_status;
    }
}
