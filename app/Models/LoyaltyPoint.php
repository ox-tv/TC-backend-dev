<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function scopeActive($query){
        $query->whereNotNull('activated_at')->where('activated_at', '<=', Carbon::now());
        return $query;
    }

    public function scopeNotCalculated($query){
        $query->where('is_calculated', false);
        return $query;
    }


    // Relations
    public function user(){
        return $this->setConnection('mysql')->belongsTo('App\Models\User');
    }
}
