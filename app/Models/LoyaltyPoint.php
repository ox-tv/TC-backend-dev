<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class LoyaltyPoint extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'loyalty_points';

    protected $casts = [
        'activated_at' => 'datetime'
    ];

    protected $dates = [
        'date'
    ];

    protected $attributes = [
        'amount' => 0,
    ];

    public $timestamps = false;

    const TYPE_REFERRER = 1;
    const TYPE_REFERRAL = 2;

    const TYPE_TEXT = [
        self::TYPE_REFERRER => 'referrer',
        self::TYPE_REFERRAL => 'referral',
    ];


    protected static function booted()
    {
        static::addGlobalScope('orderByDate', function (Builder $builder) {
            $builder->orderBy('date', 'ASC');
        });
    }


    // Relations
    public function user(){
        return $this->setConnection('mysql')->belongsTo('App\Models\User');
    }
}
