<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    protected $casts = ['content' => 'array'];

    public function scopeIdOrPage($query, $idOrPage)
    {
        $query->where(function ($q) use ($idOrPage){
            $q->when(is_numeric($idOrPage), function ($q) use ($idOrPage){
                $q->where('id', $idOrPage);
            })->when(!is_numeric($idOrPage), function ($q) use ($idOrPage){
                $q->where('page', $idOrPage);
            });
        });

        return $query;
    }
}
