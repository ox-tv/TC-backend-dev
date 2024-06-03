<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class VideoShareLink extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'video_share_links';

    const UPDATED_AT = null;

    public function scopeWhereDate($query, $column, $carbonDate)
    {
        return $query->where($column, '>=', (clone $carbonDate)->startOfDay())
            ->where($column, '<=', (clone $carbonDate)->endOfDay());
    }


    public function video(){
        return $this->setConnection('mysql')->belongsTo('App\Models\Video');
    }


    public function getTotalViewsAttribute(){
        return VideoShareLinkStatistics::where('referrer_id', $this->user_id)
            ->where('video_id', $this->video_id)
            ->count();
    }
}
