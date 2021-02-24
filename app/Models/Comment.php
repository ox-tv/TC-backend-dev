<?php

namespace App\Models;

use App\Models\Scopes\OrderDescScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted()
    {
        static::addGlobalScope(new OrderDescScope);
    }

    // scopes

    public function scopeHasVideo($query){
        $query->whereHas('video');
        return $query;
    }

    public function scopeInVideos($query, $videos){
        if(is_array($videos) && count($videos)>0){
            return $query->whereIn('video_id', $videos);
        }
        return $query;
    }

    // Relations

    public function video(){
        return $this->belongsTo('App\Models\Video');
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function parent(){
        return $this->hasOne('App\Models\Comment', 'parent_id');
    }

    public function likedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->where('relation', CommentUser::LIKED_RELATION);
    }

    public function dislikedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->where('relation', CommentUser::DISLIKED_RELATION);
    }

    public function getIsLikedAttribute(){
        if(auth('api')->check()){
            if($this->likedBy()->find(auth('api')->user()->id)){
                return true;
            }
        }

        return false;
    }

    public function getIsDislikedAttribute(){
        if(auth('api')->check()){
            if($this->dislikedBy()->find(auth('api')->user()->id)){
                return true;
            }
        }

        return false;
    }

    public function replies(){
        return $this->hasMany('App\Models\Comment', 'parent_id', 'id');
    }
}
