<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    const TYPE_DEFAULT = 1;
    const TYPE_THUMB = 2;
    const TYPE_STAR = 3;

    const TYPE_TEXT = [
        self::TYPE_DEFAULT => 'default',
        self::TYPE_THUMB => 'thumb',
        self::TYPE_STAR => 'star',
    ];

    protected $attributes = [
        'type' => self::TYPE_DEFAULT,
    ];

    // Scopes

    public function scopeType($query, $typeText){
        $query->where('type', array_flip(self::TYPE_TEXT)[$typeText]);
        return $query;
    }

    public function scopeLocation($query, $location){
        $query->where('location', $location);
        return $query;
    }

    public function scopeUser($query, $userId){
        $query->where('user_id', $userId);
        return $query;
    }

    public function scopeEmail($query, $keyword){
        $query->where('email', 'LIKE', '%'.$keyword.'%');
        return $query;
    }


    // Relations

    public function user(){
        return $this->belongsTo('App\Models\User')->withTrashed();
    }


    // Attributes

    public function getTypeTextAttribute()
    {
        return self::TYPE_TEXT[$this->type]?? $this->type;
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = (is_numeric($value))? $value : array_flip(self::TYPE_TEXT)[$value];
    }

}
