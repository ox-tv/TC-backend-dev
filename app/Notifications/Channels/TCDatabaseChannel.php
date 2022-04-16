<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class TCDatabaseChannel extends DatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        //$message = $notification->toVoice($notifiable);

        // Send notification to the $notifiable instance...
    }
}