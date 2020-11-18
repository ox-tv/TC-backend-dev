<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;
    const STATUS_ARCHIVED = 3;
    const STATUS_SUSPENDED = 4;

    use HasFactory;
    use SoftDeletes;

    public function scopeDraft($query){
        $query->where('status', self::STATUS_DRAFT);
        return $query;
    }

    public function scopePublished($query){
        $query->where('status', self::STATUS_PUBLISHED);
        return $query;
    }

    public function scopeArchived($query){
        $query->where('status', self::STATUS_ARCHIVED);
        return $query;
    }

    public function scopeSuspended($query){
        $query->where('status', self::STATUS_SUSPENDED);
        return $query;
    }

    public function scopeMine($query){
        if(auth()->check()){
            $query->where('user_id', auth()->user()->id);
        }
        return $query;
    }
}
