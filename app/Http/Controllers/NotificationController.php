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
use App\Models\Video;
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

    public function markASRead($id)
    {
        $user = auth('api')->user();

        $user->notifications()->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }

    public function send()
    {
        $user = auth('api')->user();
        $message = Message::inRandomOrder()->first();
        $video = Video::inRandomOrder()->first();
        $comment = Comment::inRandomOrder()->first();
        $channel = Channel::inRandomOrder()->first();

        //Admin notifications
        $user->notify(new NewMessage('admin',
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ]
        ));

        $user->notify(new ReplyMessage('admin',
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ]
        ));

        $user->notify(new ReportVideo('admin',
            [
                'video' => VideoMinimalItem::make($video),
                'report_count' => 2
            ]
        ));
        $user->notify(new ReportComment('admin',
            [
                'comment' => CommentItem::make($comment),
                'report_count' => 3
            ]
        ));

        $user->notify(new NewPublisherRequest('admin',
            [
                'message' => MessageItem::make($message),
                'user' => UserMinimalItem::make($user),
                'channel_name' => 'Hassan guli'
            ]
        ));

        $user->notify(new NewImportRequest('admin',
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
                'youtube_url' => 'https://youtube.com/Xsdiglfm843985'
            ]
        ));

        // Publisher notifications
        $user->notify(new NewMessage('publisher',
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ]
        ));

        $user->notify(new ReplyMessage('publisher',
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ]
        ));

        $user->notify(new HideVideo('publisher',
            [
                'video' => videoMinimalItem::make($video)
            ]
        ));

        $user->notify(new DeleteVideo('publisher',
            [
                'video' => videoMinimalItem::make($video)
            ]
        ));

        $user->notify(new UpdateChannelStatus('publisher', [
            'prev_status' => Channel::STATUS_TEXT[Channel::STATUS_PUBLISHED],
            'current_status' => Channel::STATUS_TEXT[Channel::STATUS_FREEZE],
        ]));

        $user->notify(new ImportRequestAccepted('publisher',
            [
                'channel' => ChannelMinimalItem::make($channel)
            ]
        ));

        $user->notify(new ImportRequestCompleted('publisher',
            [
                'channel' => ChannelMinimalItem::make($channel)
            ]
        ));

        $user->notify(new PublisherApproved('user'));

        return response()->json(['message' => 'ok']);
    }
}
