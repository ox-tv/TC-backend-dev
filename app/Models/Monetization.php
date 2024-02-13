<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Monetization extends Model
{
    // Status
    const STATUS_UNPAID = 1;
    const STATUS_PAID = 2;

    const STATUS_TEXT = [
        self::STATUS_UNPAID => 'unpaid',
        self::STATUS_PAID => 'paid',
    ];

    protected $table = 'monetization';

    protected $casts = [
        'month' => 'date'
    ];


}
