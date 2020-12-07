<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    // Scopes

    public function scopeMine($query){
        if(auth()->check()){
            $query->where('user_id', auth()->user()->id);
        }
        return $query;
    }

    // Relations

    public function owner(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function videos(){
        return $this->belongsToMany('App\Models\Video');
    }
}
