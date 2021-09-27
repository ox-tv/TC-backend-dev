<?php

namespace App\Http\Controllers;

use App\Http\Resources\Notification\NotificationItem;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\CustomNotification;
use App\Notifications\TCNotification\TCNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        })->with([
            'entity' => function($q){ $q->withTrashed(); }
        ])->paginate();

        foreach ($notifications as $notification){
            $notification->read_at = $notification->pivot->read_at;
        }

        return NotificationItem::collection($notifications);
    }

    public function index_sent_by_admin(Request $request)
    {
        $notifications = Notification::whereNotNull('sender_id')->orderBy('created_at', 'DESC')->with([
            'from',
            'entity' => function($q){ $q->withTrashed(); }
        ])->paginate();

        return NotificationItem::collection($notifications);
    }

    public function markASRead($id)
    {
        $user = auth('api')->user();

        $user->unreadNotifications()->updateExistingPivot($id, [
            "read_at" => now(),
        ]);

        return response()->json(['message' => 'ok']);
    }

    public function allMarkASRead($scope)
    {
        $user = auth('api')->user();

        if (!in_array($scope, Notification::SCOPE_TEXT)){
            abort(404);
        }

        $user->unreadNotifications()->where(function ($query) use ($scope){
            $query->where('notifications.scope', array_flip(Notification::SCOPE_TEXT)[$scope])
                ->orWhere('notifications.scope', Notification::SCOPE_GLOBAL);
        })->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }

    public function unReadNotificationsCount($scope)
    {
        $user = auth('api')->user();

        if (!in_array($scope, Notification::SCOPE_TEXT)){
            abort(404);
        }

        $count = $user->unreadNotifications()->where(function ($query) use ($scope){
            $query->where('notifications.scope', array_flip(Notification::SCOPE_TEXT)[$scope])
                ->orWhere('notifications.scope', Notification::SCOPE_GLOBAL);
        })->count();

        return response()->json(['count' => $count]);
    }

    public function store(Request $request, $scope)
    {
        $userGroupText = Notification::USER_GROUP_TEXT;

        $request->validate([
            'message' => 'required',
            'user_group' => [
                'required',
                Rule::in(Notification::USER_GROUP_TEXT),
            ],
            'user_ids' => [
                Rule::requiredIf(function () use ($request, $userGroupText) {
                    return $request->get("user_group") == $userGroupText[Notification::USER_GROUP_CUSTOM];
                }),
            ],
            'user_ids.*' => ['exists:users,id']
        ]);


        $userGroup = $request->get("user_group");

        $usersQuery = User::query();

        switch ($userGroup){
            case $userGroupText[Notification::USER_GROUP_ALL]:
                break;
            case $userGroupText[Notification::USER_GROUP_HERO]:
                $usersQuery = $usersQuery->isHero();
                break;
            case $userGroupText[Notification::USER_GROUP_NON_HERO]:
                $usersQuery = $usersQuery->isNonHero();
                break;
            case $userGroupText[Notification::USER_GROUP_CUSTOM]:
            default:
            $usersQuery = $usersQuery->whereIn('id', $request->get("user_ids", []));
        }

        if($scope == 'publisher'){
            $usersQuery = $usersQuery->publishers();
        }

        $scope = $scope == 'user'? 'global' : $scope;

        $users = $usersQuery->get();

        $message = $request->get('message');

        //\Illuminate\Support\Facades\Notification::send($users, new CustomNotification($scope, $message));
        TCNotification::send($users, new CustomNotification($scope, $userGroup, ['message' => $message]));

        return response()->json(['message' => 'ok']);
    }

}
