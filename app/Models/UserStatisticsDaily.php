<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserStatisticsDaily extends Model
{
    protected $fillable = ['user_id','date'];

    protected $table = 'user_statistics_daily';

    public $timestamps = false;

    protected $casts = [
        //
    ];


    protected static function booted()
    {
        static::addGlobalScope('orderByDate', function (Builder $builder) {
            $builder->orderBy('date', 'ASC');
        });
    }


    // Relations
    public function user(){
        return $this->belongsTo('App\Models\User');
    }


    public function calcPoints()
    {
        $pointsPerWatchVideoAsHero = config('user.points.per_watch_video_as_hero');
        $pointsPerWatchVideoAsNonHero = config('user.points.per_watch_video_as_non_hero');
        $pointsPerCommentLikedAsHero = config('useer.points.per_comment_liked_as_hero');
        $pointsPerCommentLikedAsNonHero = config('user.points.per_comment_liked_as_non_hero');
        $pointsPerReferrerAsHero = config('user.points.per_referrer_as_hero');
        $pointsPerReferrerAsNonHero = config('user.points.per_referrer_as_non_hero');

        $result = 0;

        $result += ($pointsPerWatchVideoAsHero * $this->video_watch_count_as_hero);
        $result += ($pointsPerWatchVideoAsNonHero * $this->video_watch_count_as_non_hero);

        $result += ($pointsPerCommentLikedAsHero * $this->comment_liked_count_as_hero);
        $result += ($pointsPerCommentLikedAsNonHero * $this->comment_liked_count_as_non_hero);

        $result += ($pointsPerReferrerAsHero * $this->referral_count_as_hero);
        $result += ($pointsPerReferrerAsNonHero * $this->referral_count_as_non_hero);

        return $this->points = $result;
    }
}
