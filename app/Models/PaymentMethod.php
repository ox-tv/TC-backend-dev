<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{

    public function pricing(){
        return $this->hasMany(Pricing::class);
    }
}
