<?php

namespace App\Http\Controllers;

use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\Comment\CommentItem;
use App\Http\Resources\Message\MessageItem;
use App\Http\Resources\Notification\NotificationItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use App\Notifications\CustomNotification;
use App\Notifications\DeleteVideo;
use App\Notifications\HideVideo;
use App\Notifications\ImportRequestAccepted;
use App\Notifications\ImportRequestCompleted;
use App\Notifications\NewImportRequest;
use App\Notifications\NewMessage;
use App\Notifications\NewPublisherRequest;
use App\Notifications\PublisherApproved;
use App\Notifications\ReplyMessage;
use App\Notifications\ReportComment;
use App\Notifications\ReportVideo;
use App\Notifications\UpdateChannelStatus;
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

    public function index_sent_by_admin(Request $request)
    {
        $notifications = Notification::whereNotNull('data->from')->with(['notifiable'])->paginate();

        return NotificationItem::collection($notifications);
    }

    public function markASRead($id)
    {
        $user = auth('api')->user();

        $user->unreadNotifications()->where('id', $id)->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }

    public function allMarkASRead($scope)
    {
        $user = auth('api')->user();

        $user->unreadNotifications()->where('data->scope', $scope)->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }

    public function unReadNotificationsCount($scope)
    {
        $user = auth('api')->user();

        return response()->json(['count' => $user->unreadNotifications()->where('data->scope', $scope)->count()]);
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

        $users = $users_query->get();

        $message = $request->get('message');

        \Illuminate\Support\Facades\Notification::send($users, new CustomNotification($scope, $message));

        return response()->json(['message' => 'ok']);
    }

}
