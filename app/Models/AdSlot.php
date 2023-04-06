<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdSlot extends Model
{
    protected $table = 'ad_slots';

    protected $fillable = ['date', 'tier'];

    protected $casts = [
        'date' => 'date',
        'price' => 'float',
        'quantity' => 'int',
    ];


    // Relations
    public function campaign(){
        return $this->belongsTo('App\Models\AdCampaign', 'ad_campaign_id', 'id')->withTrashed();
    }


    // Attributes

}
