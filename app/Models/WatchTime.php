<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchTime extends Model
{
    protected $table = 'watch_times';

    public const AllRowsCachePeriod = 24 * 60 * 60;
    public const LastRowCachePeriod = 60 * 60;

    const MERGE_STATUS_MERGING = 1;
    const MERGE_STATUS_MERGED = 2;
}
