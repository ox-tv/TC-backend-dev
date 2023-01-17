<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class MonetizePoint extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'monetize_points';

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

    const TYPE_REFERRAL = 1;
    const TYPE_VIDEO_LIKED = 2;
    const TYPE_VIDEO_VIEWED = 3;
    const TYPE_SUBSCRIPTION = 4;

    const STATUS_TYPE = [
        self::TYPE_REFERRAL => 'referral',
        self::TYPE_VIDEO_LIKED => 'video_liked',
        self::TYPE_VIDEO_VIEWED => 'video_viewed',
        self::TYPE_SUBSCRIPTION => 'subscription',
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
    public function channel(){
        return $this->setConnection('mysql')->belongsTo('App\Models\Channel');
    }
}
