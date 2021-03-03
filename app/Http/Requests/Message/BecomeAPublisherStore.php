<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class BecomeAPublisherStore extends FormRequest
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
            'selfie' => 'required|string',
            'youtube_url' => 'required|string',
            'other_url' => 'nullable',
            'current_password' => 'required|password'
        ];
    }

}
