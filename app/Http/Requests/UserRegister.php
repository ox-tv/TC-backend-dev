<?php

namespace App\Http\Requests;

use Amir\Permission\Models\Role;
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
            'referral_code' => strtoupper($this->request->get('referral_code')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required', 'string', 'email', 'max:255',
                function($attribute, $value, $fail){
                    // check if user is deleted
                    $isDeleted = User::where('email', $value)->withTrashed()->whereNotNull('deleted_at')->exists();
                    if ($isDeleted) {
                        $fail(__('auth.account_deleted'));
                    }
                },
                /*Rule::unique('users', 'email')->where(function($q) use($publisherRoleId) {
                    $q->whereNull('role_id')->orWhere('role_id', $publisherRoleId);
                })->whereNotNull('email_verified_at')*/
            ],
            'password' => ['required', 'string', 'min:8'],
            'referral_code' => ['nullable', 'string', Rule::exists('users', 'referral_code')],
            'captcha' => 'required|captcha_api:' . request('captcha_key') . ',math'
        ];
    }
}
