<?php

namespace App\Http\Controllers;


use App\Http\Requests\PublisherRegister;
use App\Http\Resources\ChannelSummaryCollection;
use App\Models\Channel;
use App\Models\Department;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class PublisherController extends Controller
{
    public function scoreBoard(){
        return new ChannelSummaryCollection(Channel::published()->paginate(50));
    }

    public function register(PublisherRegister $request){

        $user = User::where('email', $request->get('email'))->whereNull('email_verified_at')->first();

        if(is_null($user)){
            $user = new User();
        }

        $verificationCode = rand(111111, 999999);

        if (config('app.env') == 'local') {
            $verificationCode = 111111;
            $user->status = User::STATUS_ACTIVE;
        }

        $user->email = $request->get('email');
        $user->password = Hash::make($request->get('password'));
        $user->verification_code = $verificationCode;
        $user->save();

        $message = new Message();

        $message->subject = trans("publisher.application_subject");

        $message->message = trans('publisher.application_message', [
            'email' => $request->get('email'),
            'youtube_url' => $request->get('youtube_url'),
            'verification_url' => $request->get('verification_url')
        ]);

        $message->image = $request->get('image');

        $department = Department::firstOrCreate(['name' => 'Publisher Applications']);

        $message->department()->associate($department);

        $message->user()->associate($user);

        $message->save();

        return response()->json([
            'email' => $request->input('email'),
            'message' => __('publisher.messages.wait_for_verification'),
        ]);

    }

    public function confirm(Request $request, User $user){
        $reason = $request->get('reason');

        //TODO:: save reason as a message

        $user->role_id = Role::firstOrCreate(['name' => 'publisher'])->id;
        $user->save();

        $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Applications'])->id;

        Message::where([
                'user_id' => $user->id,
                'department_id' => $publisherApplicationDepartmentId
            ]
        )->delete();

        return UserItem::make($user);

    }

    public function reject(Request $request, User $user){
        $reason = $request->get('reason');

        //TODO:: save reason as a message

        return UserItem::make($user);

    }
}
