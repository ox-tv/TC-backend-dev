<?php

namespace App\TCNotification\Channels;

use App\TCNotification\BroadcastEvent;
use Exception;

class TCBroadcastChannel
{
    public function send($notifiables, $notificationResource)
    {
        //dd($notifiables);
        try {
            foreach ($notifiables as $user){
                broadcast(new BroadcastEvent($user, $notificationResource));
            }
        } catch (Exception $exception){

        }

    }
}
