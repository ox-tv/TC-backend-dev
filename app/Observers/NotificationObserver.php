<?php

namespace App\Observers;

use App\Models\Notification;

class NotificationObserver
{
    public function saving(Notification $notification)
    {
        if (!is_numeric($notification->scope)){
            $notification->scope = array_flip(Notification::SCOPE_TEXT)[$notification->scope];
        }

        if (!is_numeric($notification->user_group)){
            $notification->user_group = array_flip(Notification::USER_GROUP_TEXT)[$notification->user_group];
        }
    }

    /**
     * Handle the Notification "created" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function created(Notification $notification)
    {
        //
    }

    /**
     * Handle the Notification "updated" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function updated(Notification $notification)
    {
        //
    }

    /**
     * Handle the Notification "deleted" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function deleted(Notification $notification)
    {
        $notification->deleted_by = auth('api')->id();
        $notification->save();
    }

    /**
     * Handle the Notification "restored" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function restored(Notification $notification)
    {
        //
    }

    /**
     * Handle the Notification "force deleted" event.
     *
     * @param  \App\Models\Notification  $notification
     * @return void
     */
    public function forceDeleted(Notification $notification)
    {
        //
    }
}
