<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class VideoShareLinkStatistics extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'video_share_link_statistics';

    const UPDATED_AT = null;


    public function scopeWhereDate($query, $column, $carbonDate)
    {
        return $query->where($column, '>=', (clone $carbonDate)->startOfDay())
            ->where($column, '<=', (clone $carbonDate)->endOfDay());
    }
}
