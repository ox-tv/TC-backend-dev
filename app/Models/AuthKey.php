<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AuthKey extends Model
{
    protected $fillable = [];


    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

}
