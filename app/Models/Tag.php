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
    const CREATION_SCOPE_IMPORTER = 4;

    const CREATION_SCOPE_TEXT = [
        self::CREATION_SCOPE_ADMIN => 'admin',
        self::CREATION_SCOPE_PUBLISHER => 'publisher',
        self::CREATION_SCOPE_USER => 'user',
        self::CREATION_SCOPE_IMPORTER => 'importer',
    ];

    // Status field values
    const STATUS_PUBLISHED = 1;
    const STATUS_DELISTED = 2;

    const STATUS_TEXT = [
        self::STATUS_PUBLISHED => 'published',
        self::STATUS_DELISTED => 'delisted',
    ];


    public function scopePublished($query){
        $query->where('status', self::STATUS_PUBLISHED);
        return $query;
    }

    public function scopeHasVideo($query){
        $query->whereHas('videos');
        return $query;
    }

    public function scopeFeatured($query){
        $query->active();
        $query->where('featured', 1);
        return $query;
    }

    public function scopeSearchName($query, $keyword){
        $query->where('name', 'LIKE', $keyword.'%');
        return $query;
    }

    // Relations

    public function videos(){
        return $this->belongsToMany('App\Models\Video');
    }

    public function favoritedByUsers(){
        return $this->belongsToMany('App\Models\User');
    }


    // Attributes

    public function getFavoritedByUsersCountAttribute(){
        return $this->favoritedByUsers()->count();
    }

    public function getVideosCountAttribute(){
        return $this->videos()->count();
    }

    public function getStatusTextAttribute(){
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }

    public function getCreationScopeTextAttribute(){
        return self::CREATION_SCOPE_TEXT[$this->creation_scope]?? $this->creation_scope;
    }
}
