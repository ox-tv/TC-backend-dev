<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountDeletion extends Model
{
    protected $table = 'account_deletion';

    public $timestamps = ["created_at"];
    const UPDATED_AT = null;

    protected $primaryKey = 'user_id';

    protected $casts = [
        'expired_at' => 'datetime'
    ];


    // Relations

    public function user(){
        return $this->belongsTo('App\Models\User');
    }
}
