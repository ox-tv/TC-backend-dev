<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\BecomeAPublisherStore;
use App\Http\Requests\Message\MessageStore;
use App\Http\Resources\Message\MessageCollection;
use App\Http\Resources\Message\MessageItem;
use App\Models\Department;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\User;
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
        $isAdmin = $request->is('api/admin/messages');
        if($isAdmin){
            $query = Message::query();

            $messages = $query->paginate();
            return MessageCollection::make($messages);
        }
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

        $message = new Message();

        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = auth("api")->id();

        if ($request->is("api/admin/messages")){
            $message->subject = $request->get("subject");
            $message->department_id = $request->get("department_id");
            $message->can_reply = $request->get("can_reply") == "yes";
            $message->type = array_flip(Message::TYPE_TEXT)[$request->get("type")]?? null;
            $message->user_group = array_flip($user_group_text)[$request->get("user_group")??"custom"];
            $user_group = $request->get("user_group");

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

            if ($request->get("can_reply") == "yes"){

                foreach ($user_ids as $user_id){

                    $message->save();

                    $message_user = new MessageUser();
                    $message_user->user_id = $user_id;
                    $message_user->message_id = $message->id;
                    $message_user->status = MessageUser::STATUS_NEW;
                    $message_user->save();

                    $message = $message->replicate();
                }

            }else{

                $message->save();

                foreach ($user_ids as $user_id){

                    $message_user = new MessageUser();
                    $message_user->user_id = $user_id;
                    $message_user->message_id = $message->id;
                    $message_user->status = MessageUser::STATUS_NEW;
                    $message_user->save();
                }
            }
        }

        if ($request->is("api/messages")){
            $message->subject = $request->get("subject");
            $message->department_id = $request->get("department_id");
            $message->save();
        }

        if ($request->is("api/admin/messages/{$reply_to}/reply")){
            $message->parent_id = $request->route("reply_to");
            $message->save();
        }

        if ($request->is("api/messages/{$reply_to}/reply")){
            $message->parent_id = $request->route("reply_to");
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
    public function show($id)
    {

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
        //
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
