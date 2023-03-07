<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdDiscount extends Model
{
    protected $table = 'ad_discounts';

    protected $fillable = ['tier'];

    public $timestamps = false;

    protected $casts = [
        'start_at' => 'date',
        'end_at' => 'date'
    ];

    const TYPE_FIXED = 1;
    const TYPE_PERCENT = 2;

    const TYPE_TEXT = [
        self::TYPE_FIXED => 'fixed',
        self::TYPE_PERCENT => 'percent',
    ];


    // Relations


    // Attributes
    public function getTypeTextAttribute(){
        return self::TYPE_TEXT[$this->type]?? $this->type;
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = (is_numeric($value))? $value : array_flip(self::TYPE_TEXT)[$value];
    }

}
