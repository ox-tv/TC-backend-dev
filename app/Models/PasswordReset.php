<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordReset extends Model
{
    protected $table = 'password_resets';

    public $timestamps = ["created_at"];
    const UPDATED_AT = null;


    // Relations

    public function user(){
        return $this->belongsTo('App\Models\User', "email", "email");
    }
}
