<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    // Relations

    public function reportable()
    {
        return $this->morphTo();
    }
}
