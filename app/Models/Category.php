<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'slug'];

    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 2;

    public function scopeActive($query){
        $query->where('status', self::STATUS_ACTIVE);
        return $query;
    }

    public function scopeHasVideo($query){
        $query->whereHas('videos')->orWhereHas("main_videos");
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

    public function main_videos(){
        return $this->hasMany('App\Models\Video',"category_id");
    }
}
