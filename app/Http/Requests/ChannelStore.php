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
                }),
            ],
            'language' => ['nullable','exists:languages,id'],
            'website' => 'sometimes|nullable|url',
            'instagram' => ['sometimes', 'nullable', 'url', 'regex:/^(http(s)?:\/\/)?(www.)?instagram.com\/([A-Za-z0-9_.]{1,30})$/'],
            'facebook' => ['sometimes', 'nullable', 'url', 'regex:/^(http(s)?:\/\/)?(www.)?facebook.com\/([a-zA-Z0-9.]{1,})$/'],
            'twitter' => ['sometimes', 'nullable', 'url', 'regex:/^(http(s)?:\/\/)?(www.)?twitter.com\/([a-zA-Z0-9_]{1,15})$/'],
            'telegram' => ['sometimes', 'nullable', 'url'],
            'reddit' => ['sometimes', 'nullable', 'url'],
            'linkedin' => ['sometimes', 'nullable', 'url'],
            'tiktok' => ['sometimes', 'nullable', 'url'],
            'user_id' => 'sometimes|exists:users,id'
        ];
    }

}
