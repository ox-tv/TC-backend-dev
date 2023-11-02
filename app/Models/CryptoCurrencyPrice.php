<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class CryptoCurrencyPrice extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = ['crypto_currency_id','price', 'last_updated'];

    //protected $table = 'channel_statistics_daily';
    protected $collection = 'crypto_currency_prices';

    protected $casts = [
        'crypto_currency_id' => 'integer',
        'price' => 'float',
    ];

    protected $dates = [
        'last_updated'
    ];

    public $timestamps = false;


    // Relations
    public function cryptoCurrency(){
        return $this->setConnection('mysql')->belongsTo(CryptoCurrency::class);
    }
}
