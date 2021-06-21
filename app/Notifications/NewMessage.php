<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessage extends Notification
{
    use Queueable;

    private $scope;
    private $payload;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($scope, $payload)
    {
        $this->scope = $scope;
        $this->payload = $payload;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }


    public function toArray($notifiable)
    {
        return [
            'payload' => $this->payload,
            'scope' => $this->scope,
            'type' => 'NewMessage',
        ];
    }
}
