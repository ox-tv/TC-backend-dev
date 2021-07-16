<?php

namespace App\Http\Controllers;

use App\Http\Resources\Notification\NotificationItem;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();

        $scope = Notification::SCOPE_USER;

        if ($request->is('api/admin/notifications')){
            $scope = Notification::SCOPE_ADMIN;
        }elseif ($request->is('api/publisher/notifications')){
            $scope = Notification::SCOPE_PUBLISHER;
        }

        $notifications = $user->notifications()->where(function ($query) use ($scope){
            $query->where('scope', $scope)
                ->orWhere('scope', Notification::SCOPE_GLOBAL);
        })->with(['entity'])->paginate();

        return NotificationItem::collection($notifications);
    }

    public function index_sent_by_admin(Request $request)
    {
        $notifications = Notification::whereNotNull('sender_id')->orderBy('created_at', 'DESC')->with(['from','entity'])->paginate();

        return NotificationItem::collection($notifications);
    }

    public function markASRead($id)
    {
        $user = auth('api')->user();

        if ($user->unreadNotifications()->where('id', $id)->exists()){
            $user->notifications()->updateExistingPivot($id, [
                "read_at" => now(),
            ]);
        }

        return response()->json(['message' => 'ok']);
    }

    public function allMarkASRead($scope)
    {
        $user = auth('api')->user();

        if (!in_array($scope, Notification::SCOPE_TEXT)){
            abort(404);
        }

        $user->unreadNotifications()->where(function ($query) use ($scope){
            $query->where('scope', $scope)
                ->orWhere('scope', 'global');
        })->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }

    public function unReadNotificationsCount($scope)
    {
        $user = auth('api')->user();

        return response()->json(['count' => $user->unreadNotifications()->where(function ($query) use ($scope){
            $query->where('data->scope', $scope)
                ->orWhere('data->scope', 'global');
        })->count()]);
    }

    public function store(Request $request, $scope)
    {
        $user_group_text = Notification::USER_GROUP_TEXT;

        $users_query = User::query();

        switch ($request->get("user_group")){
            case $user_group_text[Notification::USER_GROUP_ALL]:
                break;
            case $user_group_text[Notification::USER_GROUP_HERO]:
                $users_query = $users_query->isHero();
                break;
            case $user_group_text[Notification::USER_GROUP_NON_HERO]:
                $users_query = $users_query->isNonHero();
                break;
            case $user_group_text[Notification::USER_GROUP_CUSTOM]:
            default:
                $users_query = $users_query->whereIn('id', $request->get("user_ids", []));
        }

        if($scope == 'publisher'){
            $users_query = $users_query->publishers();
        }

        $scope = $scope == 'user'? 'global' : $scope;

        $users = $users_query->get();

        $message = $request->get('message');

        \Illuminate\Support\Facades\Notification::send($users, new CustomNotification($scope, $message));

        return response()->json(['message' => 'ok']);
    }

}
