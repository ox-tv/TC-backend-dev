<?php

namespace App\Http\Requests\Message;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageStore extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // define rules for specified routes
        $default_rule = [
            'subject' => 'required',
            'message' => 'required',
            "image" => ["nullable"],
        ];

        $admin_store_rule = [
            "subject" => ["required"],
            "message" => ["required"],
            "image" => ["nullable"],
            "can_reply" => ["required", Rule::in(["yes","no"])],
            "user_group" => ["required", Rule::in(Message::USER_GROUP_TEXT)],
            "department_id" => [
                Rule::requiredIf(function () { return $this->get("can_reply") == "yes";}),
                "exists:departments,id"
            ],
            "type" => ["nullable", Rule::in(Message::TYPE_TEXT)],
            "user_ids" => [
                Rule::requiredIf(function () {
                    return is_null($this->get("user_group"))
                        || $this->get("user_group") == Message::USER_GROUP_TEXT[Message::USER_GROUP_CUSTOM];
                })
            ],
        ];

        $admin_reply_rule = [
            "message" => ["required"],
            "image" => ["nullable"],
        ];

        $user_store_rule = [
            "subject" => ["required"],
            "message" => ["required"],
            "image" => ["nullable"],
            "department_id" => ["required", "exists:departments,id"],
        ];

        $user_reply_rule = [
            "message" => ["required"],
            "image" => ["nullable"],
        ];

        // check witch one of rules will return
        $reply_to = $this->route("reply_to");

        if ($this->is("api/admin/messages")){
            return $admin_store_rule;
        }

        if ($this->is("api/admin/messages/{$reply_to}/reply")){
            return $admin_reply_rule;
        }

        if ($this->is("api/messages")){
            return $user_store_rule;
        }

        if ($this->is("api/messages/{$reply_to}/reply")){
            return $user_reply_rule;
        }

        return $default_rule;
    }

}
