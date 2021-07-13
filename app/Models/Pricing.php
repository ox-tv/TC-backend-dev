<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    protected $table = 'pricing';

    public function paymentMethod(){
        return $this->belongsTo(PaymentMethod::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(){
        return $this->belongsToMany('App\Models\User')->withTimestamps();
    }
}
