<?php

namespace App\Http\Controllers;


use Amir\Permission\Models\Role;
use App\Http\Requests\PublisherRegister;
use App\Http\Resources\ChannelSummaryCollection;
use App\Http\Resources\UserItem;
use App\Mail\PublisherApprovedMail;
use App\Models\Channel;
use App\Models\Department;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PublisherController extends Controller
{
    public function scoreBoard(Request $request){

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
            'channel_name' => $request->get('channel_name'),
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

        Mail::to($user->email)
            ->queue(new PublisherApprovedMail());

        return UserItem::make($user);
    }

    public function reject(Request $request, User $user){
        $reason = $request->get('reason');

        //TODO:: save reason as a message

        return UserItem::make($user);

    }
}
