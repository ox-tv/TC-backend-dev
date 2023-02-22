<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdPricing extends Model
{
    protected $table = 'ads_pricings';

    protected $fillable = ['date', 'tier', 'price'];

    public $timestamps = false;

    protected $casts = [
        'date' => 'date'
    ];


    // Relations


    // Attributes

}
