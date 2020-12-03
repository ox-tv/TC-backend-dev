<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;
    const STATUS_ARCHIVED = 3;
    const STATUS_SUSPENDED = 4;

    const UPLOAD_METHOD_DIRECT = 1;
    const UPLOAD_METHOD_YOUTUBE = 2;

    use HasFactory;
    use SoftDeletes;

    public function scopeDraft($query){
        $query->where('status', self::STATUS_DRAFT);
        return $query;
    }

    public function scopePublished($query){
        $query->where('status', self::STATUS_PUBLISHED);
        return $query;
    }

    public function scopeArchived($query){
        $query->where('status', self::STATUS_ARCHIVED);
        return $query;
    }

    public function scopeSuspended($query){
        $query->where('status', self::STATUS_SUSPENDED);
        return $query;
    }

    public function scopeMine($query){
        if(auth()->check()){
            $query->where('user_id', auth()->user()->id);
        }
        return $query;
    }

    // filters by time

    public function scopeWeek($query){
        $query->whereBetween('published_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        return $query;
    }

    public function scopeMonth($query){
        $query->whereBetween('published_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        return $query;
    }

    // search scopes

    public function scopeSearchTitle($query, $keyword){
        $query->where('title', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    public function scopeSearchDescription($query, $keyword){
        $query->where('description', 'LIKE', '%'.$keyword.'%');
        return $query;
    }


    // relations
    public function categories(){
        return $this->belongsToMany('App\Models\Category');
    }

    public function likedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->where('relation', UserVideo::LIKED_RELATION);
    }

    public function dislikedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->where('relation', UserVideo::DISLIKED_RELATION);
    }

    public function comments(){
        return $this->hasMany('App\Models\Comment');
    }

    public function playlists(){
        return $this->belongsToMany('App\Models\Playlist');
    }


    // Attributes
    public function getRatingAttribute(){
        return UserVideo::where('video_id', $this->id)->sum('relation');
    }

}
