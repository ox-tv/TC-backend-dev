<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\BecomeAPublisherStore;
use App\Http\Resources\Message\MessageCollection;
use App\Http\Resources\Message\MessageItem;
use App\Models\Department;
use App\Models\Message;
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $message = new Message();

        $message->subject = $request->get("subject");
        $message->message = $request->get("message");
        $message->image = $request->get("image");
        $message->user_id = auth("api")->id();
        $message->department_id = $request->get("department_id");
        $message->status = Message::STATUS_NEW;
        $message->parent_id = $request->route("replay_id");

        $message->save();

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
