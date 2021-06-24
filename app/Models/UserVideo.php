<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVideo extends Model
{
    use HasFactory;

    protected $table = 'user_video';

    const LIKED_RELATION = 1;
    const DISLIKED_RELATION = -1;
    const BOOKMARKED_RELATION = 0;


}
