<?php

namespace App\Events\User;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BuyingHeroMemberShipCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $pricingUser;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $pricingUser)
    {
        $this->user = $user;
        $this->pricingUser = $pricingUser;
    }

}
