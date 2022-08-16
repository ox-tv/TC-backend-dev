<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagUser extends Model
{
    use HasFactory;

    protected $fillable = ['tag_id', 'user_id'];

    protected $table = 'tag_user';
    
}
