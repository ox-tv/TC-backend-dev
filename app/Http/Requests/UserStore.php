<?php

namespace App\Http\Requests;

use Amir\Permission\Models\Role;
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
                    $user = User::where('email', $value)->withTrashed()->first();
                    if ($user && $user->deleted_at) {
                        $fail(__('auth.account_deleted'));
                    }
                },
                Rule::unique('users', 'email')->where(function($q) {
                    $adminRole = Role::firstOrCreate(['name' => User::ADMIN_ROLE]);
                    $publisherRole = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE]);

                    if (request()->is('api/admin/admins')){
                        $q->where('role_id', $adminRole->id);
                    }else{
                        $q->where(function($q) use($publisherRole) {
                            $q->whereNull('role_id')
                                ->orWhere('role_id', $publisherRole->id);
                        });
                    }
                })->whereNotNull('email_verified_at')->whereNull('deleted_at'),
            ],
            'username' => [
                'nullable', 'string', 'between:4,14',
                CustomRule::forbiddenWords($forbiddenWords),
                CustomRule::uniqueTrimmed(User::PUNCTUATION_MARKS, 'users', 'username'),
            ],
            'avatar' => 'nullable|string',
            'eth_address' => 'nullable|string',
            'role_id' => 'nullable|exists:roles,id',
        ];
    }

}
