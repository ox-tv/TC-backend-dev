<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 2;

    const STATUS_TEXT = [
        self::STATUS_INACTIVE => 'inactive',
        self::STATUS_ACTIVE => 'active',
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public function scopeActive($query){
        $query->where('status', self::STATUS_ACTIVE);
        return $query;
    }

    public function pricing(){
        return $this->hasMany(Pricing::class);
    }
}
