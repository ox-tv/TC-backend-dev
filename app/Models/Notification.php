<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{

    // user group field values
    const USER_GROUP_CUSTOM = 1;
    const USER_GROUP_ALL = 2;
    const USER_GROUP_PUBLISHER = 3;
    const USER_GROUP_HERO = 4;
    const USER_GROUP_NON_HERO = 5;

    const USER_GROUP_TEXT = [
        self::USER_GROUP_CUSTOM => 'custom',
        self::USER_GROUP_ALL => 'all',
        self::USER_GROUP_HERO => 'hero',
        self::USER_GROUP_NON_HERO => 'non-hero',
    ];
}
