<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'status', 'creation_scope'];

    // Creation Scope Field Values
    const CREATION_SCOPE_ADMIN = 1;
    const CREATION_SCOPE_PUBLISHER = 2;
    const CREATION_SCOPE_USER = 3;

    const CREATION_SCOPE_TEXT = [
        self::CREATION_SCOPE_ADMIN => 'admin',
        self::CREATION_SCOPE_PUBLISHER => 'publisher',
        self::CREATION_SCOPE_USER => 'user',
    ];

    // Status field values
    const STATUS_PUBLISH = 1;
    const STATUS_DELIST = 2;

    const STATUS_TEXT = [
        self::STATUS_PUBLISH => 'publish',
        self::STATUS_DELIST => 'delist',
    ];

    public function scopeHasVideo($query){
        $query->whereHas('videos');
        return $query;
    }

    public function scopeFeatured($query){
        $query->active();
        $query->where('featured', 1);
        return $query;
    }

    // Relations

    public function videos(){
        return $this->belongsToMany('App\Models\Video');
    }

    public function likedByUsers(){
        return $this->belongsToMany('App\Models\User');
    }
}
