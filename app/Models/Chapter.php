<?php

namespace App\Models;

use App\Models\Scopes\OrderByFromASCScope;
use App\Models\Scopes\OrderDescScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new OrderByFromASCScope());
    }

    // Relations

    public function video(){
        return $this->belongsTo(Video::class)->withTrashed();
    }
}
