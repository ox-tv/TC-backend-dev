<?php

namespace App\Models;

use App\Repository\Eloquent\UserRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name'
    ];


    // Scopes
    public function scopeSearchTitle($query, $keyword){
        $query->where('name', 'LIKE', '%'.$keyword.'%');
        return $query;
    }


    // Relations


    // Attribute

    public function getAvatarThumbnailsAttribute()
    {
        if (!$this->attributes['avatar_url']){
            return [];
        }

        foreach ($urls = getThumbnails($this->attributes['avatar_url']) as $key => $value){
            $urls[$key] = $value;
        }

        return $urls;
    }


    // Mutators
    public function setAvatarUrlAttribute($value)
    {
        $this->attributes['avatar_url'] = explode('?', $value)[0];
    }
}
