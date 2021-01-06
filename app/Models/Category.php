<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 2;

    public function scopeActive($query){
        $query->where('status', self::STATUS_ACTIVE);
        return $query;
    }

    // Relations

    public function videos(){
        return $this->belongsToMany('App\Models\Video');
    }
}
