<?php

namespace App\Models;

use App\Models\Scopes\OrderDescScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    const STATUS_PUBLIC = 1;
    const STATUS_PRIVATE = 2;

    const STATUS_TEXT = [
        self::STATUS_PUBLIC => 'public',
        self::STATUS_PRIVATE => 'private',
    ];

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
        if(auth('api')->check()){
            $query->where('user_id', auth('api')->user()->id);
        }
        return $query;
    }

    public function scopePublic($query){
        $query->where('status', self::STATUS_PUBLIC);
        return $query;
    }


    // Relations

    public function owner(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function channel()
    {
        return $this->hasOneThrough(
            Channel::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id');
    }

    public function videos(){
        return $this->belongsToMany('App\Models\Video');
    }


    // Attributes

    public function getTotalVideosCountAttribute(){
        return $this->videos()->count();
    }

    public function getPublishedVideosCountAttribute(){
        return $this->videos()->where('status', Video::STATUS_PUBLISHED)->count();
    }

    public function getStatusTextAttribute(){
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }
}
