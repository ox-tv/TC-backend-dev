<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    protected $casts = ['content' => 'array'];

    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;

    const STATUS_TEXT = [
        self::STATUS_DRAFT => 'draft',
        self::STATUS_PUBLISHED => 'published',
    ];

    public function scopeStatus($query, $status)
    {
        $status = is_numeric($status)? $status : array_flip(self::STATUS_TEXT)[$status];

        $query->where('status', $status);

        return $query;
    }

    public function scopeIdOrKey($query, $idOrKey)
    {
        $query->where(function ($q) use ($idOrKey){
            $q->when(is_numeric($idOrKey), function ($q) use ($idOrKey){
                $q->where('id', $idOrKey);
            })->when(!is_numeric($idOrKey), function ($q) use ($idOrKey){
                $q->where('key', $idOrKey);
            });
        });

        return $query;
    }

    // Relations
    public function lastModifiedBy(){
        return $this->belongsTo('App\Models\User', 'last_modified_by')->withTrashed();
    }

    // Attributes
    public function getStatusTextAttribute()
    {
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = (is_numeric($value))? $value : array_flip(self::STATUS_TEXT)[$value];
    }
}
