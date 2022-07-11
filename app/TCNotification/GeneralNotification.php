<?php

namespace App\TCNotification;

use Carbon\Carbon;
use \App\Models\Notification;

class GeneralNotification
{
    public $type;
    public $scope;
    public $entityType;
    public $entityId;
    public $userGroup;
    public $payload;
    public $from;
    public $publishedAt;

    public function __construct($type, $scope, $payload, $options = [])
    {
        $this->type = $type;
        $this->scope = $scope;
        $this->payload = $payload;
        $this->userGroup = $options['user_group']??
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM];
        $this->from = $options['from']?? null;
        $this->publishedAt = $options['published_at']?? Carbon::now();
        $this->entityType = $options['entity_type']?? null;
        $this->entityId = $options['entity_id']?? null;
    }

    public function via()
    {
        //return [];
        return ['broadcast'];
    }
}
