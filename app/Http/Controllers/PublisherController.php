<?php

namespace App\Http\Controllers;


use Amir\Permission\Models\Role;
use App\Http\Requests\PublisherRegister;
use App\Http\Resources\ChannelSummaryCollection;
use App\Http\Resources\Message\MessageItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Http\Resources\UserItem;
use App\Mail\PublisherApprovedMail;
use App\Mail\PublisherRejectedMail;
use App\Mail\PublisherVerificationMail;
use App\Mail\VerificationMail;
use App\Models\Channel;
use App\Models\Department;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\Notification;
use App\Models\Option;
use App\Models\User;
use App\Notifications\NewPublisherRequest;
use App\Notifications\PublisherApproved;
use App\Notifications\PublisherRejected;
use App\Notifications\ReplyMessage;
use App\Notifications\TCNotification\TCNotification;
use App\Repository\MessageRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublisherController extends Controller
{
    private $messageRepository;

    public function __construct(MessageRepositoryInterface $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function scoreBoard(Request $request)
    {
        $filters = $request->get('filters', []);

        $timeFilter = Arr::get($filters, 'time');
        $searchFilter = Arr::get($filters, 'search');

        $query = Channel::published();

        switch ($timeFilter){
            case 'week': {
                $query->week();
                break;
            }

            case 'month': {
                $query->month();
                break;
            }

            case 'year': {
                $query->year();
                break;
            }
        }

        if($searchFilter){
            $query->SearchTitle($searchFilter);
        }

        $query->orderBy('points', 'desc');

        $channels = $query->paginate(50);

        return new ChannelSummaryCollection($channels);
    }

    public function register(PublisherRegister $request){

        $user = User::where('email', $request->get('email'))->whereNull('email_verified_at')->first();

        if(is_null($user)){
            $user = new User();
        }

        $user->email = $request->get('email');
        $user->password = Hash::make($request->get('password'));
        $user->save();

        // Create verification token and send to user email
        $token = sha1($user->id . time());
        $user->verification_code = $token;
        $user->save();

        $link = config('general.PUBLISHER_EMAIL_VERIFICATION_URL').$token;
        Mail::to($user->email)
            ->queue(new PublisherVerificationMail($link));

        // Create channel for user
        $channel = new Channel();
        $channel->name = $request->get('channel_name');
        $channel->slug = Str::slug($request->get('channel_name'));
        $channel->user_id = $user->id;
        $channel->status = Channel::STATUS_DRAFT;
        $channel->save();


        // Send publisher request message to admin
        $department = Department::firstOrCreate(['name' => 'Publisher Applications']);

        $message_data = [
            'subject' => trans("publisher.application_subject"),
            'message' => trans('publisher.application_message', [
                'email' => $user->email,
                'channel_name' => $request->get('channel_name'),
                'platform' => $request->get('platform')
            ]),
            'user_id' => $user->id,
            'can_reply' => true,
            'department_id' => $department->id,
        ];

        $message = $this->messageRepository->storeUser($user->id, $message_data);

        // Check if platform is YouTube then send a message to user and ask about him/her YouTube information
        if (strtolower($request->get('platform')) == 'youtube'){
            $admin = User::admins()->first();

            $replyMessage = new Message();
            $replyMessage->subject = $message->subject;
            $replyMessage->message = trans('publisher.application_reply_for_youtube_platform_users');
            $replyMessage->user_id = $admin->id;
            $replyMessage->parent_id = $message->id;
            $replyMessage->department_id = $message->department_id;
            $replyMessage->can_reply = $message->can_reply;
            $replyMessage->type = $message->type;
            $replyMessage->save();

            $message->users()->updateExistingPivot($user->id, ["status" => MessageUser::STATUS_REPLIED_BY_ADMIN]);

            TCNotification::send(collect([$user]), new ReplyMessage(
                Notification::SCOPE_TEXT[Notification::SCOPE_USER],
                Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                [
                    'message' => MessageItem::make($replyMessage->load(['user', 'department'])),
                ],
                get_class($replyMessage),
                $replyMessage->id
            ));
        }

        $admins = User::admins()->get();

        TCNotification::send($admins, new NewPublisherRequest(
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
                'user' => UserMinimalItem::make($user),
                'channel_name' => $request->get('channel_name')
            ],
            get_class($message),
            $message->id
        ));

        return response()->json([
            'email' => $request->input('email')?? $user->email,
            'message' => __('publisher.messages.wait_for_verification'),
        ]);
    }

    public function confirm(Request $request, User $user){
        $reason = $request->get('reason');

        //TODO:: save reason as a message

        $user->role_id = Role::firstOrCreate(['name' => 'publisher'])->id;
        $user->save();

        // Check if channel name is unique then publish it
        $channel = $user->channel;

        $alreadyTaken = Channel::where('id', '!=', $channel->id)
            ->where('name', $channel->name)
            ->whereIn('status', [Channel::STATUS_FREEZE, Channel::STATUS_PUBLISHED])->exists();
        if ($alreadyTaken){
            $channel->name = $user->email;
            // TODO:: Can notify to user too
        }

        $channel->status = Channel::STATUS_PUBLISHED;
        $channel->save();


        $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Applications'])->id;

        Message::where([
                'user_id' => $user->id,
                'department_id' => $publisherApplicationDepartmentId
            ]
        )->delete();

        TCNotification::send(collect([$user]), new PublisherApproved(
            Notification::SCOPE_TEXT[Notification::SCOPE_USER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [],
            get_class($user),
            $user->id
        ));

        Mail::to($user->email)
            ->queue(new PublisherApprovedMail());

        return UserItem::make($user);
    }

    public function reject(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required',
            'message_id' => [
                'required',
                Rule::exists('messages','id')->where(function ($query) use ($user) {
                    return $query->whereNull('parent_id')->where('user_id', $user->id);
                }),
            ]
        ]);

        $reason = $request->get('reason');
        $message_id = $request->get('message_id');
        $parent_message = Message::find($message_id);
        $option_key = 'report_video_reasons';

        $reasons = json_decode(Option::where("key", $option_key)->first()->value, true) ?? [];
        if(($key = array_search($reason, array_column($reasons, 'key'))) !== false ){
            $reason = $reasons[$key]->value;
        }

        $message = new Message();
        $message->subject = $parent_message->subject;
        $message->message = "Your publisher request rejected \n Reason: {$reason}";
        $message->user_id = auth("api")->id();
        $message->parent_id = $parent_message->id;
        $message->department_id = $parent_message->department_id;
        $message->can_reply = $parent_message->can_reply;
        $message->type = $parent_message->type;
        $message->user_group = $parent_message->user_group;
        $message->save();

        $parent_message->users()->updateExistingPivot($user->id, ["status" => MessageUser::STATUS_CLOSE]);

        TCNotification::send(collect([$user]), new PublisherRejected(
            Notification::SCOPE_TEXT[Notification::SCOPE_USER],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message_id' => $message_id,
                'reason' => $reason,
            ],
            get_class($user),
            $user->id
        ));

        Mail::to($user->email)
            ->queue(new PublisherRejectedMail($reason));

        return UserItem::make($user);
    }
}
