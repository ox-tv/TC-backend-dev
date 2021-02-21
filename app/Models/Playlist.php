<?php

namespace App\Models;

use App\Models\Scopes\OrderDescScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new OrderDescScope);

        self::saved(function($model){
            if(is_null($model->url_hash) && !is_null($model->id)){
                $model->url_hash = encode_id(str_pad($model->id,10,0,STR_PAD_RIGHT));
                $model->save();
            }
        });
    }

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
