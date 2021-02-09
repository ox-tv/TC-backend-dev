<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Channel extends Model
{
    use HasFactory, SoftDeletes;

    public $fillable = [
      'name', 'user_id'
    ];

    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;
    const STATUS_ARCHIVED = 3;
    const STATUS_SUSPENDED = 4;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scopes

    public function scopeDraft($query){
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePublished($query){
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeArchived($query){
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeSuspended($query){
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    public function scopeMine($query){
        if(Auth::check()){
            return $query->where('user_id', Auth::user()->id);
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
