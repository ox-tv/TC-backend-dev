<?php

namespace App\Models\Logs;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class LogCommentLikedOnce extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'log_comment_liked_once';


}
