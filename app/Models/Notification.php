<?php

namespace App\Models;

use App\Models\Scopes\WherePublishedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends Model
{
    use HasFactory;

    protected $casts = [
        'payload' => 'array'
    ];

    // user group field values
    const USER_GROUP_CUSTOM = 1;
    const USER_GROUP_ALL = 2;
    const USER_GROUP_HERO = 3;
    const USER_GROUP_NON_HERO = 4;

    const USER_GROUP_TEXT = [
        self::USER_GROUP_CUSTOM => 'custom',
        self::USER_GROUP_ALL => 'all',
        self::USER_GROUP_HERO => 'hero',
        self::USER_GROUP_NON_HERO => 'non-hero',
    ];

    // Scope field values
    const SCOPE_GLOBAL = 1;
    const SCOPE_ADMIN = 2;
    const SCOPE_PUBLISHER = 3;
    const SCOPE_USER = 4;

    const SCOPE_TEXT = [
        self::SCOPE_GLOBAL => 'global',
        self::SCOPE_ADMIN => 'admin',
        self::SCOPE_PUBLISHER => 'publisher',
        self::SCOPE_USER => 'user',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new WherePublishedScope());
    }

    public function users(){
        return $this->belongsToMany('App\Models\User')->withPivot(["read_at"]);
    }

    public function from(){
        return $this->belongsTo('App\Models\User', 'sender_id');
    }

    public function entity(){
        return $this->morphTo();
    }
}
