<?php

namespace App\Http\Requests;

use App\Models\Video;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VideoStore extends FormRequest
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
            'categories.*.id' => 'exists:categories,id',
            'category' => 'exists:categories,id',
            'status' => Rule::in([Video::STATUS_TEXT[Video::STATUS_DRAFT], Video::STATUS_TEXT[Video::STATUS_PUBLISHED]]),
            'youtube_link' => 'url'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
                if($this->request->get('youtube_link')){
                    $parsedUrl = parse_url($this->request->get('youtube_link'),1);

                    if(!Str::contains(Str::lower($parsedUrl), 'youtube.com')) {
                        $validator->errors()->add('YouTube Link', 'video.validation.not_youtube_link');

                    }
                }
        });
    }
}
