<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoCurrency extends Model
{
    // Scopes

    public function scopeSearchName($query, $keyword){
        $query->where('name', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    public function scopeSearchSymbol($query, $keyword){
        $query->where('symbol', 'LIKE', '%'.$keyword.'%');
        return $query;
    }


    // Relations

    public function videos()
    {
        return $this->belongsToMany('App\Models\Video', 'crypto_currency_video');
    }

}
