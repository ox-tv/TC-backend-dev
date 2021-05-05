<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    // Scopes
    public function scopeVideo($query){
        $query->where('reportable_type', Video::class);
        return $query;
    }

    public function scopeChannel($query){
        $query->where('reportable_type', Channel::class);
        return $query;
    }

    public function scopeComment($query){
        $query->where('reportable_type', Comment::class);
        return $query;
    }

    // Relations

    public function reportable()
    {
        return $this->morphTo();
    }

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function reported_user(){
        return $this->belongsTo('App\Models\User', 'reported_user_id');
    }
}
