<?php

namespace App\Http\Controllers;


use Amir\Permission\Models\Role;
use App\Http\Requests\PublisherRegister;
use App\Http\Resources\ChannelSummaryCollection;
use App\Http\Resources\Message\MessageItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Http\Resources\UserItem;
use App\Mail\PublisherApprovedMail;
use App\Mail\VerificationMail;
use App\Models\Channel;
use App\Models\Department;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\NewPublisherRequest;
use App\Notifications\PublisherApproved;
use App\Notifications\TCNotification\TCNotification;
use App\Repository\MessageRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

        if (config('app.env') == 'local') {
            $token = 111111;
            $user->status = User::STATUS_ACTIVE;
        }

        $user->verification_code = $token;

        $user->save();

        $link = config('general.EMAIL_VERIFICATION_URL').$token;
        Mail::to($user->email)
            ->queue(new VerificationMail($link));


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
                'message' => MessageItem::make($message),
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

    public function reject(Request $request, User $user){
        $reason = $request->get('reason');

        //TODO:: save reason as a message

        return UserItem::make($user);

    }
}
