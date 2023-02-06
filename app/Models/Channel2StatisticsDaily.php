<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class Channel2StatisticsDaily extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = ['channel_id','video_id','date'];

    //protected $table = 'channel_statistics_daily';
    protected $collection = 'channel2_statistics_daily';

    protected $casts = [
        //
    ];

    protected $dates = [
        'date'
    ];

    protected $attributes = [
        // Channel attributes
        'subscribers_hero' => 0,
        'subscribers_non_hero' => 0,
        'subscribers_total' => 0,
        'unsubscribers_hero' => 0,
        'unsubscribers_non_hero' => 0,
        'unsubscribers_total' => 0,
        'upload_videos_total' => 0,
        'published_videos' => 0,
        'unpublished_videos' => 0,

        // Video attribtutes
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
    ];

    public $timestamps = false;


    /*protected static function booted()
    {
        static::addGlobalScope('orderByDate', function (Builder $builder) {
            $builder->orderBy('date', 'ASC');
        });
    }*/


    // Relations
    public function channel(){
        return $this->setConnection('mysql')->belongsTo('App\Models\Channel');
    }

    public function video(){
        return $this->setConnection('mysql')->belongsTo('App\Models\Video');
    }
}
