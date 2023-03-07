<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdCampaign extends Model
{
    protected $table = 'ad_campaigns';

    protected $fillable = ['name'];

    protected $casts = [
        'data' => 'array'
    ];

    const STATUS_DRAFT = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_PAUSED= 3;
    const STATUS_ARCHIVED= 4;

    const STATUS_TEXT = [
        self::STATUS_DRAFT => 'draft',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_PAUSED => 'paused',
        self::STATUS_ARCHIVED => 'archived',
    ];

    public function scopeStatus($query, $status)
    {
        $status = is_numeric($status)? $status : array_flip(self::STATUS_TEXT)[$status];
        $query->where('status', $status);
        return $query;
    }


    // Relations
    public function company(){
        return $this->belongsTo('App\Models\Company')->withTrashed();
    }

    public function slots()
    {
        return $this->hasMany('App\Models\AdSlot', 'ad_campaign_id', 'id');
    }

    // Attributes
    public function getStatusTextAttribute(){
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = (is_numeric($value))? $value : array_flip(self::STATUS_TEXT)[$value];
    }
}
