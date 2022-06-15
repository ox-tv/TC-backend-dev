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

    protected $casts = [
        'email_verified_at' => 'datetime',
        'app_verified_at' => 'datetime',
    ];

    const APP_STATUS_DISABLE = 0;
    const APP_STATUS_ENABLE = 1;

    const EMAIL_STATUS_DISABLE = 0;
    const EMAIL_STATUS_ENABLE = 1;

    const APP_TYPE_GOOGLE = 1;

    const APP_TYPE_TEXT = [
        self::APP_TYPE_GOOGLE => 'google',
    ];


    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function getAppTypeTextAttribute()
    {
        return array_key_exists($this->app_type, self::APP_TYPE_TEXT)?
            self::APP_TYPE_TEXT[$this->app_type]:
            $this->app_type;
    }
}
