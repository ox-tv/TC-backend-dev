<?php

namespace App\Http\Controllers;

use App\Notifications\TestNotify;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();

        return $user->notifications()->with('notifiable')->paginate();
    }

    public function markASRead($id)
    {
        $user = auth('api')->user();

        $user->notifications()->where('id', $id)->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }

    public function send_notify()
    {
        $user = auth('api')->user();

        $user->notify(new TestNotify());

        return response()->json(['message' => 'ok']);
    }
}
