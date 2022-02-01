<?php

namespace App\Http\Requests;

use App\Models\Option;
use App\Models\Video;
use App\Rules\CustomRule;
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
        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        return [
            'thumbnail' => [
                Rule::requiredIf(function () {
                    $status = request()->get('status');
                    return $status && Video::STATUS_TEXT[Video::STATUS_PUBLISHED] == $status;
                })
            ],
            'categories.*.id' => 'exists:categories,id',
            'crypto_currencies.*' => 'exists:crypto_currencies,id',
            'category' => [
                'exists:categories,id',
                Rule::requiredIf(function () {
                    $status = request()->get('status');
                    return $status && Video::STATUS_TEXT[Video::STATUS_PUBLISHED] == $status;
                })
            ],
            'language_id' => [
                'nullable','exists:languages,id',
                Rule::requiredIf(function () {
                    $status = request()->get('status');
                    return $status && Video::STATUS_TEXT[Video::STATUS_PUBLISHED] == $status;
                })
            ],
            'status' => Rule::in([Video::STATUS_TEXT[Video::STATUS_DRAFT], Video::STATUS_TEXT[Video::STATUS_PUBLISHED]]),
            'youtube_link' => 'url',
            'user_id' => 'sometimes|exists:users,id',
            'video' => 'file|required_without:s3_url',
            's3_url' => 'url|required_without:video',
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
