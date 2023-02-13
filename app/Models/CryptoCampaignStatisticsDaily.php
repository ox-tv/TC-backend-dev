<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class CryptoCampaignStatisticsDaily extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = ['crypto_currency_id','campaign_id','date'];

    //protected $table = 'channel_statistics_daily';
    protected $collection = 'crypto_campaign_statistics_daily';

    protected $casts = [
        //
    ];

    protected $dates = [
        'date'
    ];

    protected $attributes = [
        'registered_users_click' => 0,
        'unknown_users_click' => 0,
        'total_click' => 0,
    ];

    public $timestamps = false;


    // Relations
    public function cryptoCurrency(){
        return $this->setConnection('mysql')->belongsTo(CryptoCurrency::class);
    }

    public function campaign(){
        return $this->setConnection('mysql')->belongsTo(CryptoCampaign::class);
    }
}
