<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublisherApproved extends Notification
{
    use Queueable;

    private $scope;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }


    public function toArray($notifiable)
    {
        return [
            'scope' => $this->scope,
            'type' => 'PublisherApproved',
        ];
    }
}
