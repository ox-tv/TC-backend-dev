<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FillCustomFeedTags extends Notification
{
    use Queueable;

    public $type;
    public $scope;
    public $entityType;
    public $entityId;
    public $userGroup;
    public $payload;
    public $from;
    public $publishedAt;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($scope, $userGroup, $payload, $entityType = null, $entityId = null)
    {
        $this->type = class_basename(__CLASS__);
        $this->scope = $scope;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->from = null;
        $this->userGroup = $userGroup;
        $this->payload = $payload;
        $this->publishedAt = Carbon::now()->addDay();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [];
    }
}
