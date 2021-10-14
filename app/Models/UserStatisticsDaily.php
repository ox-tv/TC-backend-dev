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
}
