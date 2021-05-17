<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoCurrency extends Model
{

    public function scopeSearchName($query, $keyword){
        $query->where('name', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    public function scopeSearchSymbol($query, $keyword){
        $query->where('symbol', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

}
