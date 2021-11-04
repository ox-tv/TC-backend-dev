<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lottery extends Model
{
    public function users(){
        return $this->belongsToMany('App\Models\User');
    }

    public function lottery_users(){
        return $this->hasMany('App\Models\LotteryUser');
    }
}
