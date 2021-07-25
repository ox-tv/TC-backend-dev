<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\BecomeAPublisherStore;
use App\Http\Requests\Message\MessageStore;
use App\Http\Resources\Message\MessageItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\Department;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\NewImportRequest;
use App\Notifications\NewMessage;
use App\Notifications\NewPublisherRequest;
use App\Notifications\ReplyMessage;
use App\Notifications\TCNotification\TCNotification;
use App\Repository\MessageRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    private $messageRepository;

    public function __construct(MessageRepositoryInterface $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Message::nullParent();

        if($request->is('api/messages')){
            $query->mine();
        }

        $messages = $query->with(['user', 'user.channel', 'users', 'users.channel', 'department'])->paginate();

        return MessageItem::collection($messages);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param MessageStore $request
     * @param null $reply_to
     * @return \Illuminate\Http\Response
     */
    public function store(MessageStore $request, $reply_to = null)
    {
        if ($request->is("api/admin/messages")){
            $message = $this->store_admin($request);
        }

        if ($request->is("api/messages")){
            $message = $this->store_user($request);
        }

        if ($request->is("api/admin/messages/{$reply_to}/reply")){
            $message = $this->reply_admin($request, $reply_to);
        }

        if ($request->is("api/messages/{$reply_to}/reply")){
            $message = $this->reply_user($request, $reply_to);
        }

        return new MessageItem($message);
    }

    private function store_admin(Request $request)
    {
        $user_group_text = Message::USER_GROUP_TEXT;
        $message = new Message();

        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = auth("api")->id();
        $message->subject = $request->get("subject");
        $message->can_reply = $request->get("can_reply");
        $message->type = array_flip(Message::TYPE_TEXT)[$request->get("type")]?? null;
        $message->user_group = array_flip($user_group_text)[$request->get("user_group")??"custom"];

        if($request->get("department_id")){
            $message->department_id = $request->get("department_id");
        }else{
            $department = Department::firstOrCreate(['name' => 'General']);
            $message->department_id = $department->id;
        }

        switch ($request->get("user_group")){
            case $user_group_text[Message::USER_GROUP_ALL]:
                $users = User::all();
                break;
            case $user_group_text[Message::USER_GROUP_PUBLISHER]:
                $users = User::Publishers()->get();
                break;
            case $user_group_text[Message::USER_GROUP_HERO]:
                $users = User::IsHero()->get();
                break;
            case $user_group_text[Message::USER_GROUP_NON_HERO]:
                $users = User::IsNonHero()->get();
                break;
            case $user_group_text[Message::USER_GROUP_CUSTOM]:
            default:
                $users = User::whereIn('id', $request->get("user_ids", []))->get();
        }

        $message_users = [];
        foreach ($users as $user){
            $message_users[$user->id] = ['status' => MessageUser::STATUS_NEW_BY_ADMIN];
        }

        DB::transaction(function () use ($message, $message_users){
            $message->save();
            $message->users()->attach($message_users);
        });


        TCNotification::send($users, new NewMessage(
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ],
            get_class($message),
            $message->id
        ));

        return $message;
    }

    private function store_user(Request $request)
    {
        if($request->get("department_id")){
            $department_id = $request->get("department_id");
        }else{
            $department = Department::firstOrCreate(['name' => 'General']);
            $department_id = $department->id;
        }

        $message_data = [
            'subject' => $request->get("subject"),
            'message' => $request->get("message"),
            'image' => $request->get("image"),
            'user_id' => auth("api")->id(),
            'can_reply' => true,
            'department_id' => $department_id,
        ];

        $message = $this->messageRepository->storeUser(auth("api")->id(), $message_data);

        $admins = User::admins()->get();

        TCNotification::send($admins, new NewMessage(
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ],
            get_class($message),
            $message->id
        ));

        return $message;
    }

    private function reply_admin(Request $request, $reply_to)
    {
        $parent_message = Message::where('id', $reply_to)->whereNull('parent_id')->firstOrFail();

        $message = new Message();

        $message->subject = $parent_message->subject;
        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = auth("api")->id();
        $message->parent_id = $parent_message->id;
        $message->department_id = $parent_message->department_id;
        $message->can_reply = $parent_message->can_reply;
        $message->type = $parent_message->type;
        $message->user_group = $parent_message->user_group;

        $message->save();

        foreach ($parent_message->users as $user){
            $parent_message->users()->updateExistingPivot($user->id, ["status" => MessageUser::STATUS_REPLIED_BY_ADMIN]);
        }

        TCNotification::send($parent_message->users, new ReplyMessage(
            Notification::SCOPE_TEXT[Notification::SCOPE_GLOBAL],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ],
            get_class($message),
            $message->id
        ));

        return $message;
    }

    private function reply_user(Request $request, $reply_to)
    {
        $parent_message = Message::where('id', $reply_to)->whereNull('parent_id')->firstOrFail();
        $user = auth("api")->user();

        $message = new Message();

        $message->subject = $parent_message->subject;
        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = $user->id;

        if ($parent_message->users()->count() > 1){

            $old_parent = $parent_message;
            $parent_message = $parent_message->replicate();
            $parent_message->save();

            $old_parent->users()->updateExistingPivot($user->id, [
                "message_id" => $parent_message->id,
            ]);
        }

        $message->parent_id = $parent_message->id;
        $message->department_id = $parent_message->department_id;
        $message->can_reply = $parent_message->can_reply;
        $message->type = $parent_message->type;
        $message->user_group = $parent_message->user_group;

        $parent_message->users()->updateExistingPivot($user->id, [
            "status" => MessageUser::STATUS_REPLIED_BY_USER,
        ]);

        $message->save();


        $admins = User::admins()->get();

        TCNotification::send($admins, new ReplyMessage(
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
            ],
            get_class($message),
            $message->id
        ));

        return $message;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $query = Message::nullParent()->where("id", $id);

        if ($request->is("api/messages/{$id}")){
            $query->mine();
        }

        $message = $query->with(['user', 'users', 'department', 'replies'])->firstOrFail();

        return MessageItem::make($message);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $is_admin = false;
        $action = "";
        $message = Message::findOrFail($id);

        if ($request->is("api/admin/messages/{$id}/seen")){
            $is_admin = true;
            $action = "seen";
        }elseif ($request->is("api/admin/messages/{$id}/close")){
            $is_admin = true;
            $action = "close";
        }elseif ($request->is("api/messages/{$id}/seen")){
            $is_admin = false;
            $action = "seen";
        }elseif ($request->is("api/messages/{$id}/close")){
            $is_admin = false;
            $action = "close";
        }else{
            abort(404);
        }

        if ($is_admin){
            $message_user = MessageUser::where([
                "message_id" => $id
            ])->firstOrFail();
        }else{
            $message_user = MessageUser::where([
                "user_id" => auth("api")->id(),
                "message_id" => $id
            ])->firstOrFail();
        }

        $status = $message_user->status;

        if ($action == "close"){
            $status = MessageUser::STATUS_CLOSE;
        }elseif ($action == "seen"){
            if($message_user->status == MessageUser::STATUS_NEW_BY_USER && $is_admin){
                $status = MessageUser::STATUS_SEEN;
            }elseif($message_user->status == MessageUser::STATUS_NEW_BY_ADMIN && !$is_admin){
                $status = MessageUser::STATUS_SEEN;
            }elseif ($message_user->status == MessageUser::STATUS_REPLIED_BY_USER && $is_admin){
                $status = MessageUser::STATUS_SEEN;
            }elseif ($message_user->status == MessageUser::STATUS_REPLIED_BY_ADMIN && !$is_admin){
                $status = MessageUser::STATUS_SEEN;
            }
        }

        $message_user->status = $status;
        $message_user->save();

        return response()->json(["status" => "ok"]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function becomeAPublisher(BecomeAPublisherStore $request){

        $user = auth('api')->user();

        $department = Department::firstOrCreate(['name' => 'Publisher Applications']);

        $message_data = [
            'subject' => trans("publisher.application_subject"),
            'message' => trans('publisher.application_message', [
                'email' => $user->email,
                'channel_name' => $request->get('channel_name'),
                'youtube_url' => $request->get('youtube_url'),
                'verification_url' => $request->get('verification_url')
            ]),
            'user_id' => $user->id,
            'can_reply' => true,
            'department_id' => $department->id,
        ];

        $message = $this->messageRepository->storeUser($user->id, $message_data);


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
            'email' => $request->input('email'),
            'message' => __('publisher.messages.wait_for_verification'),
        ]);

    }

    public function channelImportRequest(){

        $user = auth('api')->user();

        $department = Department::firstOrCreate(['name' => 'Publisher Import Request']);

        $message_data = [
            'subject' => trans("channel.request_subject"),
            'message' => trans('channel.request_message', [
                'email' => $user->email,
                'youtube_url' => $user->channel->youtube_channel_url,
            ]),
            'user_id' => $user->id,
            'can_reply' => true,
            'department_id' => $department->id,
        ];

        $message = $this->messageRepository->storeUser($user->id, $message_data);


        $admins = User::admins()->get();

        TCNotification::send($admins, new NewImportRequest(
            Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
            Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
            [
                'message' => MessageItem::make($message->load(['user', 'department'])),
                'youtube_url' => $user->channel->youtube_channel_url
            ],
            get_class($message),
            $message->id
        ));

        return new MessageItem($message);
    }
}
