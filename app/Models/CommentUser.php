<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentUser extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'comment_user';

    const LIKED_RELATION = 1;
    const DISLIKED_RELATION = -1;
    const REMEMBERED_RELATION = 0;
}
