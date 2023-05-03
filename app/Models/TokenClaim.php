<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenClaim extends Model
{
    protected $fillable = [];

    protected $table = 'token_claims';

    protected $casts = [
        'data' => 'array'
    ];

    const STATUS_PENDING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;

    const STATUS_TEXT = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_SUCCESS => 'success',
        self::STATUS_FAIL => 'fail',
    ];


    // Relations
    public function user(){
        return $this->belongsTo('App\Models\User');
    }


    // Attributes
    public function getStatusTextAttribute(){
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }
}
