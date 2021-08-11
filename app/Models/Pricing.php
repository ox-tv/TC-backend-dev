<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pricing extends Model
{
    use SoftDeletes;

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
