<?php

namespace App\Models;

use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;

class SecurityRateLimit extends Model
{
    protected $connection = 'security';
    protected $collection = 'rate_limit_default';

    const UPDATED_AT = null;

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->collection = 'rate_limit_' . Carbon::now()->format("Y-m-d");
    }

    protected $fillable = ['ip_address','user_id','route'];

}
