<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonetizationPayout extends Model
{
    // Status
    const STATUS_UNPAID = 1;
    const STATUS_PAID = 2;

    const STATUS_TEXT = [
        self::STATUS_UNPAID => 'unpaid',
        self::STATUS_PAID => 'paid',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'parameters' => 'array',
    ];


    // Relations

    public function channel(){
        return $this->belongsTo('App\Models\Channel');
    }
}
