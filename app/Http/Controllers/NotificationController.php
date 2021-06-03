<?php

namespace App\Http\Controllers;

use App\Http\Resources\Notification\NotificationItem;
use App\Notifications\TestNotify;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();

        $notifications = $user->notifications()->with(['notifiable'])->paginate();

        return NotificationItem::collection($notifications);
    }

    public function markASRead($id)
    {
        $user = auth('api')->user();

        $user->notifications()->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }
}
