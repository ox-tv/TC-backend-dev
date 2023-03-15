<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class TokenPoint extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'token_points';

    protected $casts = [
        'activate_at' => 'datetime'
    ];

    protected $dates = [
        'date'
    ];

    protected $attributes = [
        'amount' => 0,
    ];

    public $timestamps = false;

    const TYPE_PUBLISH_A_MEDIA = 1;
    const TYPE_ANSWER_A_COMMENT = 2;
    const TYPE_REFERRER_AS_PUBLISHER = 3;
    const TYPE_REFERRAL_VIA_PUBLISHER = 4;
    const TYPE_REFERRER = 5;
    const TYPE_WATCH_A_VIDEO = 6;
    const TYPE_CUSTOM_FEED_FIILED = 7;
    const TYPE_BUYING_YEARLY_HERO_MEMBERSHIP = 8;

    const TYPE_TEXT = [
        self::TYPE_PUBLISH_A_MEDIA => 'publish_a_media',
        self::TYPE_ANSWER_A_COMMENT => 'answer_a_comment',
        self::TYPE_REFERRER_AS_PUBLISHER => 'referrer_as_publisher',
        self::TYPE_REFERRAL_VIA_PUBLISHER => 'referral_via_publisher',
        self::TYPE_REFERRER => 'referrer',
        self::TYPE_WATCH_A_VIDEO => 'watch_a_video',
        self::TYPE_CUSTOM_FEED_FIILED => 'custom_feed_filled',
        self::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP => 'buying_yearly_hero_membership',
    ];


    protected static function booted()
    {
        static::addGlobalScope('orderByDate', function (Builder $builder) {
            $builder->orderBy('date', 'DESC');
        });
    }

    public function scopeActive($query){
        $query->whereNotNull('activated_at')->where('activated_at', '<=', Carbon::now());
        return $query;
    }



    // Relations
    public function user(){
        return $this->setConnection('mysql')->belongsTo('App\Models\User');
    }
}
