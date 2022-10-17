<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class VideoStatisticsDaily extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = ['video_id','channel_id','date'];

    //protected $table = 'video_statistics_daily';
    protected $collection = 'video_statistics_daily';

    protected $casts = [
        'point_details' => 'array'
    ];

    protected $dates = [
        'date'
    ];

    protected $attributes = [
        'views_hero' => 0,
        'views_non_hero' => 0,
        'views_total' => 0,
        'likes_hero' => 0,
        'likes_non_hero' => 0,
        'likes_total' => 0,
        'dislikes_hero' => 0,
        'dislikes_non_hero' => 0,
        'dislikes_total' => 0,
        'comments_hero' => 0,
        'comments_non_hero' => 0,
        'comments_total' => 0,
        'watch_time_hero' => 0,
        'watch_time_non_hero' => 0,
        'watch_time_total' => 0,
        'points' => 0,
        'point_details' => "",
    ];

    public $timestamps = false;


    protected static function booted()
    {
        static::addGlobalScope('orderByDate', function (Builder $builder) {
            $builder->orderBy('date', 'ASC');
        });
    }

    public function calcPointDetails()
    {
        $pointsPerView = config('general.points.per_view');
        $pointsPerLikeHero = config('general.points.per_like_hero');
        $pointsPerLikeNonHero = config('general.points.per_like_non_hero');
        $pointsPerDislikeHero = config('general.points.per_dislike_hero');
        $pointsPerDislikeNonHero = config('general.points.per_dislike_non_hero');

        $result = [];

        $result['views_total'] = ($this->views_total * $pointsPerView);
        $result['likes_hero'] = ($this->likes_hero * $pointsPerLikeHero);
        $result['likes_non_hero'] = ($this->likes_non_hero * $pointsPerLikeNonHero);
        $result['dislikes_hero'] = ($this->dislikes_hero * $pointsPerDislikeHero);
        $result['dislikes_non_hero'] = ($this->dislikes_non_hero * $pointsPerDislikeNonHero);

        $result['hero'] = $result['likes_hero'] - $result['dislikes_hero'];
        $result['non_hero'] = $result['views_total'] + $result['likes_non_hero'] - $result['dislikes_non_hero'];
        $result['likes'] = $result['likes_hero'] + $result['likes_non_hero'];
        $result['dislikes'] = $result['dislikes_hero'] + $result['dislikes_non_hero'];

        return $result;
    }


    // Relations
    public function video(){
        return $this->setConnection('mysql')->belongsTo('App\Models\Video');
    }

    public function channel(){
        return $this->setConnection('mysql')->belongsTo('App\Models\Channel');
    }

    // attributes
    public function getPointsAttribute($value)
    {
        return intval($value);
    }
}
