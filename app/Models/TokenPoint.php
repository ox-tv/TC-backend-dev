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
        'activate_at' => 'datetime',
        'claimable_at' => 'datetime'
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
    const TYPE_REFERRER_AS_HERO = 6;
    const TYPE_WATCH_A_VIDEO = 7;
    const TYPE_WATCH_A_VIDEO_AS_HERO = 8;
    const TYPE_CUSTOM_FEED_FIILED = 9;
    const TYPE_CUSTOM_FEED_FIILED_AS_HERO = 10;
    const TYPE_LIKED_COMMENT = 11;
    const TYPE_LIKED_COMMENT_AS_HERO = 12;
    const TYPE_BUYING_YEARLY_HERO_MEMBERSHIP = 13;
    const TYPE_BUYING_YEARLY_HERO_MEMBERSHIP_AS_HERO = 14;

    const TYPE_OTHER = 15;

    const TYPE_VIEW_VIDEO_VIA_SHARE_LINK = 16;

    const TYPE_TEXT = [
        self::TYPE_PUBLISH_A_MEDIA => 'publish_a_media',
        self::TYPE_ANSWER_A_COMMENT => 'answer_a_comment',
        self::TYPE_REFERRER_AS_PUBLISHER => 'referrer_as_publisher',

        self::TYPE_REFERRAL_VIA_PUBLISHER => 'referral_via_publisher',

        self::TYPE_REFERRER => 'referrer',
        self::TYPE_REFERRER_AS_HERO => 'referrer_as_hero',
        self::TYPE_WATCH_A_VIDEO => 'watch_a_video',
        self::TYPE_WATCH_A_VIDEO_AS_HERO => 'watch_a_video_as_hero',
        self::TYPE_CUSTOM_FEED_FIILED => 'custom_feed_filled',
        self::TYPE_CUSTOM_FEED_FIILED_AS_HERO => 'custom_feed_filled_as_hero',
        self::TYPE_LIKED_COMMENT => 'liked_comment',
        self::TYPE_LIKED_COMMENT_AS_HERO => 'liked_comment_as_hero',
        self::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP => 'buying_yearly_hero_membership',
        self::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP_AS_HERO => 'buying_yearly_hero_membership_as_hero',
        self::TYPE_VIEW_VIDEO_VIA_SHARE_LINK => 'view_video_via_share_link',

        self::TYPE_OTHER => 'other',
    ];

    const TYPE_FOR_PUBLISHER = [
        self::TYPE_PUBLISH_A_MEDIA,
        self::TYPE_ANSWER_A_COMMENT,
        self::TYPE_REFERRER_AS_PUBLISHER,
    ];

    const TYPE_FOR_HERO = [
        self::TYPE_WATCH_A_VIDEO_AS_HERO,
        self::TYPE_LIKED_COMMENT_AS_HERO,
        self::TYPE_CUSTOM_FEED_FIILED_AS_HERO,
        self::TYPE_REFERRER_AS_HERO,
        self::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP_AS_HERO,
    ];

    const TYPE_FOR_USER = [
        self::TYPE_REFERRAL_VIA_PUBLISHER,
        self::TYPE_REFERRER,
        self::TYPE_WATCH_A_VIDEO,
        self::TYPE_CUSTOM_FEED_FIILED,
        self::TYPE_LIKED_COMMENT,
        self::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP,
        self::TYPE_OTHER,
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

    // Attributes
    public function getTypeTextAttribute(){
        return self::TYPE_TEXT[$this->type]?? $this->type;
    }

    // Relations
    public function user(){
        return $this->setConnection('mysql')->belongsTo('App\Models\User');
    }
}
