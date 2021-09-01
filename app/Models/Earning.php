<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Earning extends Model
{
    // Status
    const STATUS_PENDING = 1;
    const STATUS_PAID = 2;
    const STATUS_FAILED = 3;

    const STATUS_TEXT = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_PAID => 'paid',
        self::STATUS_FAILED => 'failed',
    ];

    protected $attributes = [
        'currency' => 'USD',
    ];


    // Relations

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function transaction(){
        return $this->belongsTo('App\Models\Transaction');
    }
}
