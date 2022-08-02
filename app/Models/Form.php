<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $casts = ['data' => 'array'];

    const TYPE_CONTACT_US = 1;

    const TYPE_TEXT = [
        self::TYPE_CONTACT_US => 'contact-us',
    ];

    // Scopes

    public function scopeType($query, $typeText){
        if (!empty(array_flip(self::TYPE_TEXT)[$typeText])){
            $query->where('type', array_flip(self::TYPE_TEXT)[$typeText]);
        }
        return $query;
    }

    public function scopeUser($query, $userId){
        $query->where('user_id', $userId);
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
