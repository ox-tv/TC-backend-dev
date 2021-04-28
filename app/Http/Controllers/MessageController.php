<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\BecomeAPublisherStore;
use App\Http\Requests\Message\MessageStore;
use App\Http\Resources\Message\MessageCollection;
use App\Http\Resources\Message\MessageDetail;
use App\Http\Resources\Message\MessageItem;
use App\Models\Department;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\User;
use Illuminate\Http\Request;
use const http\Client\Curl\AUTH_ANY;

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

        $messages = $query->paginate();
        return MessageCollection::make($messages);
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
        $user_group_text = Message::USER_GROUP_TEXT;

        if ($reply_to)
            $parent_message = Message::find($reply_to);

        $message = new Message();

        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = auth("api")->id();

        if ($request->is("api/admin/messages")){
            $message->subject = $request->get("subject");
            $message->department_id = $request->get("department_id");
            $message->can_reply = $request->get("can_reply");
            $message->type = array_flip(Message::TYPE_TEXT)[$request->get("type")]?? null;
            $message->user_group = array_flip($user_group_text)[$request->get("user_group")??"custom"];
            $user_group = $request->get("user_group");
            $message->save();

            switch ($user_group){
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
                    $user_ids = User::IsNotHero()->pluck("id");
                    break;
                case $user_group_text[Message::USER_GROUP_CUSTOM]:
                default:
                    $user_ids = $request->get("user_ids", []);
            }

            foreach ($user_ids as $user_id){

                $message_user = new MessageUser();
                $message_user->user_id = $user_id;
                $message_user->message_id = $message->id;
                $message_user->status = MessageUser::STATUS_NEW;
                $message_user->save();
            }
        }

        if ($request->is("api/messages")){
            $message->subject = $request->get("subject");
            $message->department_id = $request->get("department_id");
            $message->can_reply = true;
            $message->save();

            $message_user = new MessageUser();
            $message_user->user_id = auth('api')->id();
            $message_user->message_id = $message->id;
            $message_user->status = MessageUser::STATUS_NEW;
            $message_user->save();
        }

        if ($request->is("api/admin/messages/{$reply_to}/reply")){
            $message->parent_id = $request->route("reply_to");
            $message->subject = $parent_message->subject;
            $message->save();

            $message_user = MessageUser::where([
                "message_id" => $message->id
            ])->first();dd($message_user);

            $message_user->status = MessageUser::STATUS_REPLIED_BY_ADMIN;
            $message_user->save();
        }

        if ($request->is("api/messages/{$reply_to}/reply")){

            $parent_id = null;

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

            $message->subject = $parent_message->subject;
            $message->parent_id = $parent_id;
            $message->save();
        }

        return new MessageItem($message);
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

        $message = $query->firstOrFail();

        return new MessageDetail($message);
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

        if ($action == "close"){
            $message_user->status = MessageUser::STATUS_CLOSE;
        }elseif ($action == "seen"){
            if($message_user->status == MessageUser::STATUS_NEW && $message->user_id == $message_user->user_id && $is_admin){
                $message_user->status = MessageUser::STATUS_SEEN;
            }elseif($message_user->status == MessageUser::STATUS_NEW && $message->user_id != $message_user->user_id && !$is_admin){
                $message_user->status = MessageUser::STATUS_SEEN;
            }elseif ($message_user->status == MessageUser::STATUS_REPLIED_BY_USER && $is_admin){
                $message_user->status = MessageUser::STATUS_SEEN;
            }elseif ($message_user->status == MessageUser::STATUS_REPLIED_BY_ADMIN && !$is_admin){
                $message_user->status = MessageUser::STATUS_SEEN;
            }
        }

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
        $message = new Message();

        $message->subject = trans("publisher.application_subject");

        $message->message = trans('publisher.application_message', [
            'youtube_url' => $request->get('youtube_url'),
            'other_url' => $request->get('youtube_url')
        ]);

        $message->image = $request->get('image');

        $department = Department::firstOrCreate(['name' => 'Publisher Applications']);

        $message->department()->associate($department);

        $message->save();

        return new MessageItem($message);

    }
}
