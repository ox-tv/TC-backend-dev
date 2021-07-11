<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 2;

    const STATUS_TEXT = [
        self::STATUS_INACTIVE => 'inactive',
        self::STATUS_ACTIVE => 'active',
    ];

    public function scopeActive($query){
        $query->where('status', self::STATUS_ACTIVE);
        return $query;
    }

    public function paymentMethods(){
        return $this->belongsToMany('App\Models\PaymentMethod');
    }
}
