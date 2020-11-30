<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class VideoLike extends FormRequest
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
            //
        ];
    }

    public function withValidator($validator)
    {
        $user = auth()->user();
        $video = $this->route('video');

        $validator->after(function ($validator)use ($user, $video) {
            if($video->likedBy()->where('user_id', $user->id)->first()){
                $validator->errors()->add('video', 'video.validation.already_liked');
            }
        });
    }
}
