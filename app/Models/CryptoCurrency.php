<?php

namespace App\Models;

use App\Models\Scopes\OrderByOrderASCScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CryptoCurrency extends Model
{
    protected $casts = [
        'metadata' => 'array',
        'prices' => 'array',
    ];

    const STATUS_LIST = 1;
    const STATUS_DELIST = 2;

    const STATUS_TEXT = [
        self::STATUS_LIST => 'list',
        self::STATUS_DELIST => 'delist',
    ];

    protected static function booted()
    {
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('order', 'ASC')->orderBy('id','ASC');
        });
    }

    // Scopes

    public function scopeSearchName($query, $keyword){
        $keyword = strtolower($keyword);
        $query->where('name', 'LIKE', $keyword.'%');
        return $query;
    }

    public function scopeSearchSymbol($query, $keyword){
        $keyword = strtoupper($keyword);
        $query->where('symbol', 'LIKE', $keyword.'%');
        return $query;
    }


    // Relations

    public function videos()
    {
        return $this->belongsToMany('App\Models\Video', 'crypto_currency_video');
    }

    public function cryptoCampaigns()
    {
        return $this->belongsToMany('App\Models\CryptoCampaign', 'campaign_crypto_currency');
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'crypto_currency_user');
    }


    // Attributes
    public function setRatioAttribute($value)
    {
        $this->attributes['ratio'] = $value;
    }

    public function getStatusTextAttribute(){
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }

    public function getThumbnailsAttribute(){
        return $this->metadata['image']?? '';
    }

    public function getIsFavoriteAttribute($isFavorite)
    {
        if (is_null($isFavorite)){
            $isFavorite = auth('api')->check()
                && DB::table('crypto_currency_user')
                    ->where([
                        'crypto_currency_id' => $this->id,
                        'user_id' => auth('api')->id(),
                    ])->exists();
        }

        return $isFavorite;
    }

    public function getOrderAttribute($order)
    {
        return $order < 1000000? $order : null;
    }

}