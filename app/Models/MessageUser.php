<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageUser extends Model
{
    use HasFactory;

    protected $table = 'message_user';
    public $timestamps = false;

    protected $primaryKey = null;
    public $incrementing = false;

    const STATUS_NEW_BY_ADMIN = 1;
    const STATUS_NEW_BY_USER = 6;
    const STATUS_SEEN = 2;
    const STATUS_CLOSE = 3;
    const STATUS_REPLIED_BY_ADMIN = 4;
    const STATUS_REPLIED_BY_USER = 5;

    const STATUS_TEXT = [
        self::STATUS_NEW_BY_ADMIN => 'new_by_admin',
        self::STATUS_NEW_BY_USER => 'new_by_user',
        self::STATUS_SEEN => 'seen',
        self::STATUS_CLOSE => 'close',
        self::STATUS_REPLIED_BY_ADMIN => 'replied_by_admin',
        self::STATUS_REPLIED_BY_USER => 'replied_by_user',
    ];

}
