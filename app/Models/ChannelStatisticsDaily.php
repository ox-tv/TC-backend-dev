<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelStatisticsDaily extends Model
{
    protected $fillable = ['channel_id','date'];

    protected $table = 'channel_statistics_daily';

    protected $casts = [
        //
    ];

    public $timestamps = false;


    protected static function booted()
    {
        static::addGlobalScope('orderByDate', function (Builder $builder) {
            $builder->orderBy('date', 'ASC');
        });
    }


    // Relations
    public function channel(){
        return $this->belongsTo('App\Models\Channel');
    }
}
