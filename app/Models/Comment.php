<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

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

    public function replies(){
        return $this->hasMany('App\Models\Comment', 'parent_id', 'id');
    }
}
