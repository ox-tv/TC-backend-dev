<?php

namespace App\TCNotification;

use App\Http\Resources\Notification\NotificationResource;
use App\TCNotification\Channels\TCBroadcastChannel;
use App\TCNotification\Channels\TCDatabaseChannel;
use App\TCNotification\Channels\TCMailChannel;
use RuntimeException;

class TCNotificationManager
{
    public function send($notifiables, $notification)
    {
        $driver = $this->getDriver('database');
        $notificationModel = $driver->send($notifiables, $notification);

        $this->sendOtherDrivers($notifiables, $notification, $notificationModel);
    }

    private function sendOtherDrivers($notifiables, $notification, $notificationModel)
    {
        $via = $notification->via();

        if (empty($via)){
            return;
        }

        if (in_array('broadcast', $via)){
            $notificationModel->load(['entity','from']);
            $driver = $this->getDriver('broadcast');
            $driver->send($notifiables, NotificationResource::make($notificationModel));
        }

        if (in_array('mail', $via)){
            $driver = $this->getDriver('mail');
            $driver->send($notifiables, $notificationModel);
        }
    }

    private function getDriver($channel)
    {
        $drivers = [
            'database' => new TCDatabaseChannel(),
            'broadcast' => new TCBroadcastChannel(),
            'mail' => new TCMailChannel(),
        ];

        if (empty($drivers[$channel])){
            throw new RuntimeException('Notification Channel is not found.');
        }

        return $drivers[$channel];
    }
}