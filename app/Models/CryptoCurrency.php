<?php

namespace App\Models;

use App\Models\Scopes\OrderByOrderASCScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CryptoCurrency extends Model
{
    protected $casts = [
        'metadata' => 'array',
        'prices' => 'array',
    ];

    const STATUS_LIST = 1;
    const STATUS_DELIST = 2;

    const STATUS_TEXT = [
        self::STATUS_LIST => 'list',
        self::STATUS_DELIST => 'delist',
    ];

    protected static function booted()
    {
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('order', 'ASC')->orderBy('id','ASC');
        });
    }

    // Scopes

    public function scopeSearchName($query, $keyword){
        $keyword = strtolower($keyword);
        $query->where('name', 'LIKE', $keyword.'%');
        return $query;
    }

    public function scopeSearchSymbol($query, $keyword){
        $keyword = strtoupper($keyword);
        $query->where('symbol', 'LIKE', $keyword.'%');
        return $query;
    }


    // Relations

    public function videos()
    {
        return $this->belongsToMany('App\Models\Video', 'crypto_currency_video');
    }

    // Mutators
    public function setRatioAttribute($value)
    {
        $this->attributes['ratio'] = $value;
    }

}
