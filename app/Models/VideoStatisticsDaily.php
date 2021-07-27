<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoStatisticsDaily extends Model
{
    protected $fillable = ['video_id','channel_id','date'];
    protected $table = 'video_statistics_daily';
    public $timestamps = false;


    protected static function booted()
    {
        static::addGlobalScope('orderByDate', function (Builder $builder) {
            $builder->orderBy('date', 'ASC');
        });
    }


    // Relations
    public function video(){
        return $this->belongsTo('App\Models\Video');
    }

    public function channel(){
        return $this->belongsTo('App\Models\Channel');
    }
}
