<?php

namespace App\Http\Requests;

use App\Models\Option;
use App\Models\User;
use App\Rules\CustomRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStore extends FormRequest
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
        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        return [
            'email' => [
                'required', 'string', 'email', 'max:255',
                function($attribute, $value, $fail){
                    // check if user is deleted
                    $user = User::where('email', $value)->first();
                    if ($user && $user->deleted_at) {
                        $fail(__('users.validation.account_deleted'));
                    }
                },
                Rule::unique('users', 'email')->whereNotNull('email_verified_at')],
            'username' => [
                'nullable', 'string',
                CustomRule::forbiddenWords($forbiddenWords),
                CustomRule::uniqueTrimmed(User::PUNCTUATION_MARKS, 'users', 'username')
                    ->ignore(auth('api')->id()),
            ],
            'avatar' => 'nullable|string',
            'eth_address' => 'nullable|string',
            'role_id' => 'nullable|exists:roles,id',
        ];
    }

}
