<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRegister extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ref' => strtoupper($this->request->get('ref')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
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
            'password' => ['required', 'string', 'min:8'],
            'ref' => ['nullable', 'string', Rule::exists('users', 'referral_code')],
        ];
    }
}
