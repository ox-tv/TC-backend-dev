<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    protected $fillable = [];

    protected $casts = [
        'expired_at' => 'datetime',
        'verified_at' => 'datetime',
    ];


    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }


}
