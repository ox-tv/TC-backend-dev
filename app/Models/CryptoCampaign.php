<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptoCampaign extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_ARCHIVE = 3;

    const STATUS_TEXT = [
        self::STATUS_DRAFT => 'draft',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_ARCHIVE => 'archive',
    ];

    public function scopeStatus($query, $status)
    {
        $status = is_numeric($status)? $status : array_flip(self::STATUS_TEXT)[$status];
        $query->where('status', $status);
        return $query;
    }

    public function scopeSearchName($query, $keyword){
        $query->where('name', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    // Relations
    public function crypto_currencies(){
        return $this->belongsToMany('App\Models\CryptoCurrency', 'campaign_crypto_currency');
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
