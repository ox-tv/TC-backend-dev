<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingUser extends Model
{
    protected $table = 'pricing_user';

    protected $casts = [
        'metadata' => 'array'
    ];

    const STATUS_PENDING = 1;
    const STATUS_PENDING_BLOCKCHAIN = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_FAILED = 4;
    const STATUS_CANCELED = 5;

    const STATUS_TEXT = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_PENDING_BLOCKCHAIN => 'pending_blockchain',
        self::STATUS_COMPLETED => 'completed',
        self::STATUS_FAILED => 'failed',
        self::STATUS_CANCELED => 'canceled',
    ];

    // Relations
    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
