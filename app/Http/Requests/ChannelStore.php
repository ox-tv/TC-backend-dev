<?php

namespace App\Http\Requests;

use App\Models\Channel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChannelStore extends FormRequest
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
            'name' => [
                'required',
                Rule::unique('channels')->where(function ($query) {
                    return $query->whereIn('status', [Channel::STATUS_PUBLISHED, Channel::STATUS_FREEZE]);
                })
            ],
            'website' => 'sometimes|nullable|url',
            'user_id' => 'sometimes|exists:users,id'
        ];
    }

}
