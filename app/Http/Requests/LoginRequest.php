<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LoginRequest extends FormRequest
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
        return [
            'email' => 'required|string|email',
            'password' => ['required', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if(Auth::validate(['email' => request('email'), 'password' => $this->request->get('password')])){
                $user = Auth::getLastAttempted();
                if($user->status == User::STATUS_INACTIVE) {
                    $validator->errors()->add('credentials', 'auth.inactive_account');

                }
            }

        });
    }
}
