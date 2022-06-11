<?php

namespace App\Http\Requests;

use Amir\Permission\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserResendVerification extends FormRequest
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
        $publisherRoleId = Role::firstOrCreate(['name' => User::PUBLISHER_ROLE])->id;

        return [
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::exists('users', 'email')->where(function($q) use($publisherRoleId) {
                    $q->whereNull('role_id')->orWhere('role_id', $publisherRoleId);
                })->whereNull('email_verified_at')
            ],
        ];
    }

}
