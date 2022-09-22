<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDetails extends Model
{
    protected $fillable = [];

    protected $table = 'payment_details';

    protected $attributes = [
        'is_archive' => false
    ];

    protected $casts = [
        'last_status_at' => 'datetime',
        'code_sent_at' => 'datetime',
        'is_archive' => 'boolean',
    ];

    const STATUS_NEW = 1;
    const STATUS_CODE_SENT = 2;
    const STATUS_VERIFIED = 3;
    const STATUS_EXPIRED = 4;
    const STATUS_CANCELED = 5;

    const STATUS_TEXT = [
        self::STATUS_NEW => 'new',
        self::STATUS_CODE_SENT => 'code-sent',
        self::STATUS_VERIFIED => 'verified',
        self::STATUS_EXPIRED => 'expired',
        self::STATUS_CANCELED => 'canceled',
    ];

    public function scopeVerified($query){
        $query->where('status', static::STATUS_VERIFIED);
        return $query;
    }

    public function scopeArchived($query){
        $query->where('is_archive', true);
        return $query;
    }

    public function scopeNonArchived($query){
        $query->where('is_archive', false);
        return $query;
    }

    public function scopeHasOnGoing($query){
        $query->whereIN('status', [static::STATUS_NEW, static::STATUS_CODE_SENT]);
        return $query;
    }

    // Relations
    public function user(){
        return $this->belongsTo('App\Models\User');
    }


    public function getStatusTextAttribute()
    {
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }
}
