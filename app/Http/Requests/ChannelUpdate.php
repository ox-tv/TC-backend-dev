<?php

namespace App\Http\Requests;

use App\Models\Channel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChannelUpdate extends FormRequest
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
        $thisChannelId = request()->route('channel') ?? auth('api')->user()->channel->id;
        
        return [
            'name' => [
                'nullable',
                Rule::unique('channels')->ignore($thisChannelId)->where(function ($query) {
                    return $query->whereIn('status', [Channel::STATUS_PUBLISHED, Channel::STATUS_FREEZE]);
                })
            ],
            'website' => 'sometimes|nullable|url'
        ];
    }

}
