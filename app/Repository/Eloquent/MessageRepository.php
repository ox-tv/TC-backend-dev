<?php


namespace App\Repository\Eloquent;


use App\Models\Department;
use App\Models\Message;
use App\Models\MessageUser;
use App\Repository\MessageRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class MessageRepository implements MessageRepositoryInterface
{

    public function storeUser($related_user, array $payload)
    {
        $message = new Message();

        foreach ($payload as $key => $value){
            $message->{$key} = $value;
        }

        $message->save();

        $message->users()->attach([$related_user => ['status' => MessageUser::STATUS_NEW_BY_USER]]);

        return $message;
    }
}