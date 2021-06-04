<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\BecomeAPublisherStore;
use App\Http\Requests\Message\MessageStore;
use App\Http\Resources\Message\MessageItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Models\Department;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\User;
use App\Notifications\NewImportRequest;
use App\Notifications\NewPublisherRequest;
use Illuminate\Http\Request;

class MessageController extends Controller
{
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

        $messages = $query->with(['user', 'users', 'department'])->paginate();

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

        $message->save();

        switch ($request->get("user_group")){
            case $user_group_text[Message::USER_GROUP_ALL]:
                $user_ids = User::pluck("id");
                break;
            case $user_group_text[Message::USER_GROUP_PUBLISHER]:
                $user_ids = User::Publishers()->pluck("id");
                break;
            case $user_group_text[Message::USER_GROUP_HERO]:
                $user_ids = User::IsHero()->pluck("id");
                break;
            case $user_group_text[Message::USER_GROUP_NON_HERO]:
                $user_ids = User::IsNonHero()->pluck("id");
                break;
            case $user_group_text[Message::USER_GROUP_CUSTOM]:
            default:
                $user_ids = $request->get("user_ids", []);
        }

        $message_users = [];
        foreach ($user_ids as $user_id)
            $message_users[$user_id] = ['status' => MessageUser::STATUS_NEW];

        $message->users()->attach($message_users);

        return $message;
    }

    private function store_user(Request $request)
    {
        $message = new Message();

        $message->subject = $request->get("subject");
        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = auth("api")->id();
        $message->can_reply = true;

        if($request->get("department_id")){
            $message->department_id = $request->get("department_id");
        }else{
            $department = Department::firstOrCreate(['name' => 'General']);
            $message->department_id = $department->id;
        }

        $message->save();

        $message->users()->attach([auth('api')->id() => ['status' => MessageUser::STATUS_NEW]]);

        return $message;
    }

    private function reply_admin(Request $request, $reply_to)
    {
        $parent_message = Message::where('id', $reply_to)->whereNull('parent_id')->first();

        $message = new Message();

        $message->subject = $parent_message->subject;
        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = auth("api")->id();
        $message->parent_id = $parent_message->id;

        $message->save();

        $message_user = MessageUser::where([
            "message_id" => $parent_message->id
        ])->first();

        $message->users()->updateExistingPivot($message_user->user_id, ["status"=>MessageUser::STATUS_REPLIED_BY_ADMIN]);

        return $message;
    }

    private function reply_user(Request $request, $reply_to)
    {
        $parent_message = Message::find($reply_to);

        $message = new Message();

        $message->subject = $parent_message->subject;
        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = auth("api")->id();

        if ($parent_message->users()->count() > 1){

            $new_message = $parent_message->replicate();
            $new_message->save();

            $parent_id = $new_message->id;

            $message_user = MessageUser::where([
                "user_id" => auth("api")->id(),
                "message_id" => $parent_message->id
            ])->first();
            $message_user->message_id = $parent_id;

        }else{
            $parent_id = $parent_message->id;

            $message_user = MessageUser::where([
                "message_id" => $parent_message->id
            ])->first();
        }

        $message_user->status = MessageUser::STATUS_REPLIED_BY_USER;
        $message_user->save();

        $message->parent_id = $parent_id;

        $message->save();

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
            if($message_user->status == MessageUser::STATUS_NEW && $message->user_id == $message_user->user_id && $is_admin){
                $status = MessageUser::STATUS_SEEN;
            }elseif($message_user->status == MessageUser::STATUS_NEW && $message->user_id != $message_user->user_id && !$is_admin){
                $status = MessageUser::STATUS_SEEN;
            }elseif ($message_user->status == MessageUser::STATUS_REPLIED_BY_USER && $is_admin){
                $status = MessageUser::STATUS_SEEN;
            }elseif ($message_user->status == MessageUser::STATUS_REPLIED_BY_ADMIN && !$is_admin){
                $status = MessageUser::STATUS_SEEN;
            }
        }

        $message->users()->updateExistingPivot($message_user->user_id, ["status"=> $status]);

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

        $message = new Message();

        $message->subject = trans("publisher.application_subject");

        $message->message = trans('publisher.application_message', [
            'email' => $user->email,
            'channel_name' => $request->get('channel_name'),
            'youtube_url' => $request->get('youtube_url'),
            'verification_url' => $request->get('verification_url')
        ]);

        $message->image = $request->get('image');

        $department = Department::firstOrCreate(['name' => 'Publisher Applications']);

        $message->department()->associate($department);

        $message->user()->associate($user);

        $message->save();


        $admins = User::admins()->get();

        foreach ($admins as $admin){
            $admin->notify(new NewPublisherRequest('admin',
                [
                    'message' => MessageItem::make($message),
                    'user' => UserMinimalItem::make($user),
                    'channel_name' => $request->get('channel_name')
                ]
            ));
        }

        return response()->json([
            'email' => $request->input('email'),
            'message' => __('publisher.messages.wait_for_verification'),
        ]);

    }

    public function channelImportRequest(){

        $user = auth('api')->user();

        $message = new Message();

        $message->subject = trans("channel.request_subject");

        $message->message = trans('channel.request_message', [
            'email' => $user->email,
            'youtube_url' => $user->channel->youtube_channel_url,
        ]);

        $department = Department::firstOrCreate(['name' => 'Publisher Import Request']);

        $message->department()->associate($department);

        $message->save();


        $admins = User::admins()->get();

        foreach ($admins as $admin){
            $admin->notify(new NewImportRequest('admin',
                [
                    'message' => MessageItem::make($message),
                    'user' => UserMinimalItem::make($user),
                    'youtube_url' => $user->channel->youtube_channel_url
                ]
            ));
        }

        return new MessageItem($message);
    }
}
