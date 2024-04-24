<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class VideoShareLink extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'video_share_links';

    const UPDATED_AT = null;

}
