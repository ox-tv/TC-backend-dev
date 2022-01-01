<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MagicLogin extends Model
{
    protected $table = 'magic_login';

    public $timestamps = ["created_at"];
    const UPDATED_AT = null;

    protected $casts = [
        'expired_at' => 'datetime'
    ];


    // Relations

    public function user(){
        return $this->belongsTo('App\Models\User', "email", "email");
    }
}
