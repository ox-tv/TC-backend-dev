<?php

namespace App\TCNotification\Channels;

use App\TCNotification\BroadcastEvent;

class TCBroadcastChannel
{
    public function send($notifiables, $notificationResource)
    {
        foreach ($notifiables as $user){
            broadcast(new BroadcastEvent($user, $notificationResource));
        }
    }
}
