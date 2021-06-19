<?php

namespace App\Http\Requests\Chapter;

use App\Models\Chapter;
use App\Models\Message;
use App\Models\MessageUser;
use App\Models\Video;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChapterUpdate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $video_id = $this->route('video');
        $chapter_id = $this->route('chapter');

        return Video::whereId($video_id)->mine()->exists()
            && Chapter::whereId($chapter_id)->where('video_id', $video_id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $video_id = $this->route('video');
        $chapter_id = $this->route('chapter');

        $video = Video::find($video_id);

        return [
            'from' => [
                'required',
                "lt:{$video->duration}",
                Rule::unique('chapters')->ignore($chapter_id)->where(function ($query) use($video_id) {
                    return $query->where('video_id', $video_id)
                        ->where('from', request()->get('from'));
                }),
            ],
            'title' => 'required',
        ];
    }
}
