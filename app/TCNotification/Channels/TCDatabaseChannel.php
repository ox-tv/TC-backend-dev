<?php

namespace App\TCNotification\Channels;

use App\Repository\Eloquent\NotificationRepository;

class TCDatabaseChannel
{
    public function send($notifiables, $notification)
    {
        $data = $this->getData($notifiables, $notification);

        $notificationRepository = new NotificationRepository();

        return $notificationRepository->store($notifiables, $data);
    }

    private function getData($notifiable, $notification)
    {
        return [
            'type' => $notification->type,
            'scope' => $notification->scope,
            'payload' => $notification->payload,
            'user_group' => $notification->userGroup,
            'sender_id' => $notification->from,
            'published_at' => $notification->publishedAt,
            'entity_type' => $notification->entityType,
            'entity_id' => $notification->entityId,
        ];
    }
}
