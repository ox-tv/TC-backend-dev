<?php

namespace App\TCNotification;

use Illuminate\Support\Facades\Facade;

class TCNotificationFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return TCNotificationManager::class;
    }
}