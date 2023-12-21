<?php

namespace App\Models;

use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;

class WAFSuspiciousIPAddress extends Model
{
    protected $connection = 'security';
    protected $collection = 'suspicious_ip_address';

    public $timestamps = false;

    protected $fillable = ['ip_address'];

    protected $casts = [

    ];

    public static function isExistsIP($ip)
    {
        return self::where('ip_address', $ip)->exists();
    }
}
