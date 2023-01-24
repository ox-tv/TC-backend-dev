<?php

namespace App\Http\Controllers;


use Amir\Permission\Models\Role;
use App\Events\Messages\MessageRepliedByAdmin;
use App\Events\Publisher\NewPublisherRequested;
use App\Events\Publisher\PublisherRequestApproved;
use App\Events\Publisher\PublisherRequestRejected;
use App\Http\Requests\Message\BecomeAPublisherStore;
use App\Http\Requests\PublisherRegister;
use App\Http\Resources\Channel\ChannelResource;
use App\Http\Resources\Message\MessageItem;
use App\Http\Resources\User\UserResource;
use App\Mail\PublisherVerificationMail;
use App\Models\Channel;
use App\Models\Department;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\Notification;
use App\Models\Option;
use App\Models\User;
use App\Models\UserMeta;
use TCNotification;
use App\Repository\MessageRepositoryInterface;
use App\TCNotification\GeneralNotification;
use Carbon\Carbon;
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

        $channels->append([
            'uploads_count',
            'total_views',
            'total_likes',
            'total_dislikes',
            'subscribers_count',
            'hero_subscribers_count',
        ]);

        return ChannelResource::collection($channels);
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

        // save requested channel name on user meta
        $user->meta()->updateOrCreate(
            ['key' => UserMeta::REQUESTED_CHANNEL_NAME],
            ['value' => $request->get('channel_name'),]
        );

        // Send publisher request message to admin
        $department = Department::firstOrCreate(['name' => 'Publisher Application']);

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

            TCNotification::Send(collect([$user]), new GeneralNotification(
                Notification::TYPE_REPLY_MESSAGE,
                Notification::SCOPE_TEXT[Notification::SCOPE_USER],
                ['message' => MessageItem::make($replyMessage->load(['user', 'department']))],
                [
                    'entity_type' => get_class($replyMessage),
                    'entity_id' => $replyMessage->id,
                ]
            ));
        }

        $admins = User::admins()->get();

        TCNotification::Send($admins, new GeneralNotification(
            Notification::TYPE_NEW_PUBLISHER_REQUEST,
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
                'user' => UserResource::make($user),
                'channel_name' => $request->get('channel_name')
            ],
            [
                'entity_type' => get_class($message),
                'entity_id' => $message->id,
            ]
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

        // Create channel for user
        $channelNameMeta = $user->meta()
            ->where('key', UserMeta::REQUESTED_CHANNEL_NAME)->first();

        if ($channelNameMeta){
            $channelName = $channelNameMeta->value;
            $alreadyTaken = Channel::where('name', $channelName)->exists();
        }

        if (!$channelNameMeta || $alreadyTaken){
            $channelName = $user->email;
        }

        $channel = new Channel();
        $channel->name = $channelName;
        $channel->slug = Str::slug($channelName);
        $channel->user_id = $user->id;
        $channel->status = Channel::STATUS_PUBLISHED;
        $channel->save();

        $user->username = $channel->name;
        $user->referral_code = Str::slug($channel->name);
        $user->avatar_url = $channel->avatar_url;
        $user->save();

        $channelNameMeta = $user->meta()
            ->where('key', UserMeta::REQUESTED_CHANNEL_NAME)->delete();

        // Remove publisher request message
        $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Application'])->id;

        Message::where([
                'user_id' => $user->id,
                'department_id' => $publisherApplicationDepartmentId
            ]
        )->delete();

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::PUBLISHER_REQUEST_STATUS],
            ['value' => 'confirmed',]
        );

        event(new PublisherRequestApproved($user));

        return UserResource::make($user);
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

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::PUBLISHER_REQUEST_STATUS],
            ['value' => 'rejected',]
        );

        $reason = $request->get('reason');
        $message_id = $request->get('message_id');
        $parent_message = Message::find($message_id);
        $option_key = Option::PUBLISHER_REQUEST_REJECT_REASONS;

        $reasons = Option::get($option_key)->value ?? null;
        $reasons = $reasons? json_decode($reasons, true): [];

        if(($key = array_search($reason, array_column($reasons, 'key'))) !== false ){
            $reason = $reasons[$key]['value'];
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


        event(new PublisherRequestRejected($user, $reason, $parent_message, $message));

        return UserResource::make($user);
    }


    public function becomeAPublisher(BecomeAPublisherStore $request)
    {
        $user = auth('api')->user();

        if ($user->meta()->where('key', UserMeta::PUBLISHER_REQUEST_STATUS)->exists()){
            return response()->json([
                'message' => __('publisher.duplicate_request'),
            ], 422);
        }

        // save requested channel name on user meta
        $user->meta()->updateOrCreate(
            ['key' => UserMeta::PUBLISHER_REQUEST_STATUS],
            ['value' => 'pending',]
        );

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::REQUESTED_CHANNEL_NAME],
            ['value' => $request->get('channel_name'),]
        );

        // Send publisher request message to admin
        $department = Department::firstOrCreate(['name' => 'Publisher Application']);


        // check if it's conversion or regular request
        $isConversion = $user->created_at >= Carbon::now()->subHours(24);

        $message_data = [
            'subject' => $isConversion ? trans("publisher.new_application_subject") : trans("publisher.conversion_application_subject"),
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

            event(new MessageRepliedByAdmin($replyMessage, $message));
        }

        event(new NewPublisherRequested($user, $message));

        return response()->json([
            'status' => 'ok',
            'message' => __('publisher.messages.wait_for_verification'),
        ]);
    }
}
