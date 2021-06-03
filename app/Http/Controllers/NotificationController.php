<?php

namespace App\Http\Controllers;

use App\Http\Resources\Notification\NotificationItem;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();

        $scope = 'user';

        if ($request->is('api/admin/notifications')){
            $scope = 'admin';
        }elseif ($request->is('api/publisher/notifications')){
            $scope = 'publisher';
        }

        $notifications = $user->notifications()->where('data->scope', $scope)->with(['notifiable'])->paginate();

        return NotificationItem::collection($notifications);
    }

    public function markASRead($id)
    {
        $user = auth('api')->user();

        $user->notifications()->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }
}
