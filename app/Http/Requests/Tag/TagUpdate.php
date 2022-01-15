<?php

namespace App\Http\Requests\Tag;

use App\Models\Chapter;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\Option;
use App\Models\Tag;
use App\Models\Video;
use App\Rules\CustomRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagUpdate extends FormRequest
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
        $tag_id = $this->route('tag');

        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);
        $forbiddenWords = $forbiddenWords? json_decode($forbiddenWords->value, true) : [];

        return [
            'name' => ['required', CustomRule::forbiddenWords($forbiddenWords), Rule::unique('tags', 'name')->ignore($tag_id)],
            'status' => ['nullable', Rule::in(Tag::STATUS_TEXT)],
        ];
    }
}
