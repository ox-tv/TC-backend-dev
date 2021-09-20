<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Status
    const STATUS_PENDING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_FAILED = 3;

    const STATUS_TEXT = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_COMPLETED => 'completed',
        self::STATUS_FAILED => 'failed',
    ];

    // Types
    const TYPE_DEPOSIT = 1;
    const TYPE_WITHDRAW = 2;
    const TYPE_REFUND = 3;

    const TYPE_TEXT = [
        self::TYPE_DEPOSIT => 'deposit',
        self::TYPE_WITHDRAW => 'withdraw',
        self::TYPE_REFUND => 'refund',
    ];

    protected $attributes = [
        'currency' => 'USD',
    ];


    // Relations

    public function payment_method(){
        return $this->belongsTo('App\Models\PaymentMethod');
    }

    // attributes
    public function getAmountAttribute($value)
    {
        return floatval($value);
    }
}
