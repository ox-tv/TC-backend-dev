<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class WatchTimeMongo extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'watch_times';
    protected $fillable = ['video_id', 'user_id', 'start_time', 'end_time'];

    protected $casts = [
        //
    ];

    public const AllRowsCachePeriod = 24 * 60 * 60;
    public const LastRowCachePeriod = 60 * 60;

    public function scopeWhereDate($query, $column, $carbonDate)
    {
        return $query->where($column, '>=', (clone $carbonDate)->startOfDay())
            ->where($column, '<=', (clone $carbonDate)->endOfDay());
    }
}
