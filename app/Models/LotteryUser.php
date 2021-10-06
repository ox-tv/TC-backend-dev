<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LotteryUser extends Model
{
    protected $table = 'lottery_user';

    public $timestamps = false;

    // Status
    const STATUS_PENDING = 1;
    const STATUS_PAID = 2;
    const STATUS_FAILED = 3;
    const STATUS_NA = 4;

    const STATUS_TEXT = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_PAID => 'paid',
        self::STATUS_FAILED => 'failed',
        self::STATUS_NA => 'N/A',
    ];

    protected $attributes = [
        'currency' => 'USD',
    ];


    // Relations

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function lottery(){
        return $this->belongsTo('App\Models\Lottery');
    }

    public function transaction(){
        return $this->belongsTo('App\Models\Transaction');
    }

    // attributes
    public function getAmountAttribute($value)
    {
        return floatval($value);
    }
}
