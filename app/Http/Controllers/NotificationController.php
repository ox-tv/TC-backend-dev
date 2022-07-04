<?php

namespace App\Http\Controllers;

use App\Http\Resources\Notification\NotificationItem;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\TCNotification\GeneralNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use TCNotification;

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

        $filters = $request->get('filters', []);
        $justUnreadFilter = Arr::get($filters, 'just_unread');

        $query = $user->notifications()->where(function ($query) use ($scope){
            $query->where('scope', $scope)
                ->orWhere('scope', Notification::SCOPE_GLOBAL);
        })->with([
            'entity' => function($q){ $q->withTrashed(); }
        ]);

        if ($justUnreadFilter){
            $query->wherePivotNull('read_at');
        }

        $notifications = $query->paginate();

        foreach ($notifications as $notification){
            $notification->read_at = $notification->pivot->read_at;
        }

        return NotificationResource::collection($notifications);
    }

    public function index_sent_by_admin(Request $request)
    {
        $query = Notification::whereNotNull('sender_id')->orderBy('created_at', 'DESC')->with([
            'from' => function($q){ $q->withTrashed(); },
            'DeletedBy' => function($q){ $q->withTrashed(); },
            'entity' => function($q){ $q->withTrashed(); },
            'users' => function($q){ $q->withTrashed(); }
        ]);

        $filters = $request->get('filters', []);
        $userGroupFilter = Arr::get($filters, 'user_group');
        $userIdFilter = Arr::get($filters, 'user_id');
        $justDeletedFilter = Arr::get($filters, 'just_deleted');

        if ($userGroupFilter){
            switch ($userGroupFilter){
                case 'all': {
                    $query->where('user_group', Notification::USER_GROUP_ALL);
                    $query->where(function ($query) {
                        $query->where('scope', Notification::SCOPE_USER)
                            ->orWhere('scope', Notification::SCOPE_GLOBAL);
                    });
                    break;
                }
                case 'publisher': {
                    $query->where('user_group', Notification::USER_GROUP_ALL);
                    $query->where('scope', Notification::SCOPE_PUBLISHER);
                    break;
                }
                case 'custom': {
                    $query->where('user_group', Notification::USER_GROUP_CUSTOM);
                    break;
                }
            }
        }

        if ($userIdFilter){
            $query->whereHas('users', function (Builder $query) use ($userIdFilter) {
                $query->where('id', $userIdFilter);
            });
        }

        if ($justDeletedFilter){
            $query->onlyTrashed();
        }

        $notifications = $query->paginate();

        $notifications->append(['deleted_at']);

        return NotificationResource::collection($notifications);
    }

    public function show(Request $request, $id)
    {
        $user = auth('api')->user();

        if ($request->is('api/admin/*')){
            $query = Notification::where('id', $id)
                ->with([
                    'entity' => function($q){ $q->withTrashed(); }
                ])->withTrashed();
        }else{
            $query = $user->notifications()
                ->where('id', $id)
                ->with([
                    'entity' => function($q){ $q->withTrashed(); }
                ]);
        }

        $notification = $query->firstOrFail();

        if ($request->is('api/admin/*')){
            $notification->load(['from', 'users', 'DeletedBy']);
            $notification->append(['deleted_at']);
        }else{
            $notification->read_at = $notification->pivot->read_at;
        }

        return NotificationResource::make($notification);
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
        $scopeText = Notification::SCOPE_TEXT;

        $request->validate([
            'subject' => 'sometimes|string',
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

        if($scope == $scopeText[Notification::SCOPE_PUBLISHER]){
            $usersQuery = $usersQuery->publishers();
        }

        $scope = $scope == $scopeText[Notification::SCOPE_USER]?
            $scopeText[Notification::SCOPE_GLOBAL] : $scope;

        $users = $usersQuery->get();

        $subject = $request->get('subject');
        $message = $request->get('message');

        //\Illuminate\Support\Facades\Notification::send($users, new CustomNotification($scope, $message));
        //TCNotification::send($users, new CustomNotification($scope, $userGroup, ['message' => $message]));

        TCNotification::Send($users, new GeneralNotification(
            Notification::TYPE_CUSTOM_NOTIFICATION,
            $scope,
            ['message' => $message, 'subject' => $subject],
            [
                'published_at' => Carbon::now(),
                'from' => auth('api')->id(),
                'user_group' => $userGroup,
            ]
        ));

        return response()->json(['message' => 'ok']);
    }

    public function destroy($id)
    {
        $notification = Notification::where('id', $id)->firstOrFail();

        $notification->delete();

        return response()->json(['message' => 'ok']);
    }

    public function restore($id)
    {
        $notification = Notification::where('id', $id)->onlyTrashed()->firstOrFail();

        $notification->restore();

        return response()->json(['message' => 'ok']);
    }

}
