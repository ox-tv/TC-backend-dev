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
            'email' => 'required_without:login|string|email',
            'login' => 'required_without:email|string',
            'password' => ['required', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $login = $this->request->get('email')?:$this->request->get('login');
            $login_type = filter_var($login, FILTER_VALIDATE_EMAIL)? 'email': 'username';

            if(Auth::validate([$login_type => $login, 'password' => $this->request->get('password')])){
                $user = Auth::getLastAttempted();
                if($user->status == User::STATUS_INACTIVE) {
                    $validator->errors()->add('credentials', 'auth.inactive_account');
                }
            }

        });
    }
}
