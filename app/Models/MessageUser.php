<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageUser extends Model
{
    use HasFactory;

    protected $table = 'message_user';
    public $timestamps = false;

    const STATUS_NEW = 1;
    const STATUS_READ = 2;
    const STATUS_REPLIED = 3;

    const STATUS_TEXT = [
        self::STATUS_NEW => 'new',
        self::STATUS_READ => 'read',
        self::STATUS_REPLIED => 'replied',
    ];


}
