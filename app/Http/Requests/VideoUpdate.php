<?php

namespace App\Http\Requests;

use App\Models\Option;
use App\Models\Video;
use App\Rules\CustomRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VideoUpdate extends FormRequest
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
        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        return [
            'title' => 'string',
            'categories.*.id' => 'exists:categories,id',
            'crypto_currencies.*' => 'exists:crypto_currencies,id',
            'category' => 'exists:categories,id',
            'language_id' => ['nullable','exists:languages,id'],
            'video' => 'nullable|file',
            's3_url' => 'nullable|url',
            'youtube_link' => 'url',
            'status' => Rule::in([Video::STATUS_TEXT[Video::STATUS_DRAFT], Video::STATUS_TEXT[Video::STATUS_PUBLISHED]]),
            'tags' => 'nullable|array',
            'tags.*' => ['string', CustomRule::forbiddenWords($forbiddenWords)],
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
