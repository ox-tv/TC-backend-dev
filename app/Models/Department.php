<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'scope'];

    // Scope field values
    const SCOPE_GLOBAL = 1;
    const SCOPE_PUBLISHER = 2;
    const SCOPE_USER = 3;

    const SCOPE_TEXT = [
        self::SCOPE_GLOBAL => 'global',
        self::SCOPE_PUBLISHER => 'publisher',
        self::SCOPE_USER => 'user',
    ];


    // Scopes

    public function scopeScopePublisherOrGlobal($query){
        return $query->where(function ($query) {
            $query->where('scope', self::SCOPE_PUBLISHER)
                ->orWhere('scope', self::SCOPE_GLOBAL);
        });
    }

    public function scopeScopeUserOrGlobal($query){
        return $query->where(function ($query) {
            $query->where('scope', self::SCOPE_USER)
                ->orWhere('scope', self::SCOPE_GLOBAL);
        });
    }


    // Attributes

    public function getScopeTextAttribute()
    {
        return self::SCOPE_TEXT[$this->scope]?? $this->scope;
    }
}
