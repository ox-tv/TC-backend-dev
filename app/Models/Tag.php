<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name'];

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
}
