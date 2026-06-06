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
        $video = $this->route('video');

        return [
            'title' => [
                Rule::requiredIf(function () use($video) {
                    return !$video->title;
                }), 'string'
            ],
            'thumbnail' => [
                Rule::requiredIf(function () use($video) {
                    $status = request()->get('status');
                    return !$video->thumbnail_url && $status && Video::STATUS_TEXT[Video::STATUS_PUBLISHED] == $status;
                })
            ],
            'categories.*.id' => 'exists:categories,id',
            'category' => [
                'exists:categories,id',
                Rule::requiredIf(function () use($video) {
                    $status = request()->get('status');
                    return !$video->category_id && $status && Video::STATUS_TEXT[Video::STATUS_PUBLISHED] == $status;
                })
            ],
            'language' => [
                'nullable','exists:languages,id',
            ],
            'video' => 'nullable|file',
            'file_url' => 'nullable|url',
            'youtube_link' => 'url',
            'status' => Rule::in([Video::STATUS_TEXT[Video::STATUS_DRAFT], Video::STATUS_TEXT[Video::STATUS_PUBLISHED]]),
            'tags' => 'nullable|array',
            'tags.*' => ['string', CustomRule::forbiddenWords($forbiddenWords), CustomRule::alphaSpace(), 'max:25'],
            'media_type' => ['nullable', Rule::in(Video::MEDIA_TYPE_TEXT)],
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
