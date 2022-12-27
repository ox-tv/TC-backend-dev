<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class UserStatisticsDaily extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = ['user_id','date'];

    //protected $table = 'user_statistics_daily';
    protected $collection = 'user_statistics_daily';

    public $timestamps = false;

    protected $casts = [
        //
    ];

    protected $dates = [
        'date'
    ];

    protected $attributes = [
        'video_views_count_as_hero' => 0,
        'video_views_count_as_non_hero' => 0,
        'video_views_count_total' => 0,
        'video_likes_count_as_hero' => 0,
        'video_likes_count_as_non_hero' => 0,
        'video_likes_count_total' => 0,
        'comment_likes_count_as_hero' => 0,
        'comment_likes_count_as_non_hero' => 0,
        'comment_likes_count_total' => 0,
        'comment_liked_count_as_hero' => 0,
        'comment_liked_count_as_non_hero' => 0,
        'comment_liked_count_total' => 0,
        'referral_count_as_hero' => 0,
        'referral_count_as_non_hero' => 0,
        'referral_count_total' => 0,
        'video_watch_count_as_hero' => 0,
        'video_watch_count_as_non_hero' => 0,
        'video_watch_count_total' => 0,
        'points' => 0,
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


    public function calcPoints()
    {
        $pointsPerWatchVideoAsHero = config('user.points.per_watch_video_as_hero');
        $pointsPerWatchVideoAsNonHero = config('user.points.per_watch_video_as_non_hero');
        $pointsPerCommentLikedAsHero = config('user.points.per_comment_liked_as_hero');
        $pointsPerCommentLikedAsNonHero = config('user.points.per_comment_liked_as_non_hero');
        $pointsPerReferrerAsHero = config('user.points.per_referrer_as_hero');
        $pointsPerReferrerAsNonHero = config('user.points.per_referrer_as_non_hero');

        $result = 0;

        $result += ($pointsPerWatchVideoAsHero * $this->video_watch_count_as_hero);
        $result += ($pointsPerWatchVideoAsNonHero * $this->video_watch_count_as_non_hero);

        if(auth('api')->id() != $this->user_id){
            $result += ($pointsPerCommentLikedAsHero * $this->comment_liked_count_as_hero);

            $result += ($pointsPerCommentLikedAsNonHero * $this->comment_liked_count_as_non_hero);
        }

        $result += ($pointsPerReferrerAsHero * $this->referral_count_as_hero);
        $result += ($pointsPerReferrerAsNonHero * $this->referral_count_as_non_hero);

        $this->points = $result;

        return $this->points;
    }
}
