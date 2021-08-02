<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoStatisticsDaily extends Model
{
    protected $fillable = ['video_id','channel_id','date'];

    protected $table = 'video_statistics_daily';

    protected $casts = [
        'point_details' => 'array'
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
        return $this->belongsTo('App\Models\Video');
    }

    public function channel(){
        return $this->belongsTo('App\Models\Channel');
    }
}
