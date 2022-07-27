<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentUser extends Model
{
    use HasFactory;

    protected $fillable = ['comment_id', 'user_id', 'relation'];

    protected $table = 'comment_user';

    const LIKED_RELATION = 1;
    const DISLIKED_RELATION = -1;
    const REMEMBERED_RELATION = 0;
    const MENTION_RELATION = 2;
}
