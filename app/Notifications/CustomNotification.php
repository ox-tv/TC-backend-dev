<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification
{
    use Queueable;

    private $scope;
    private $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($scope, $message)
    {
        $this->scope = $scope;
        $this->message = $message;
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
            'payload' => ['message' => $this->message],
            'scope' => $this->scope,
            'type' => 'CustomNotification',
            'from' => auth('api')->id(),
        ];
    }
}
