<?php

namespace App\Models;

use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;

class WAFNotValidRequestLog extends Model
{
    protected $connection = 'security';
    protected $collection = 'not_valid_request_default';

    const UPDATED_AT = null;

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->collection = 'not_valid_request_' . Carbon::now()->format("Y-m-d");
    }

    public function setCollection($collection) {
        $this->collection = $collection;
        return $this;
    }

    protected $fillable = ['ip_address','user_id','route'];
    protected $casts = [
        'created_at' => 'datetime'
    ];
}
