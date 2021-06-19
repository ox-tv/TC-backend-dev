<?php

namespace App\Http\Requests\Chapter;

use App\Models\Message;
use App\Models\MessageUser;
use App\Models\Video;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChapterStore extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $video_id = $this->route('video');
        return Video::whereId($video_id)->mine()->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $video_id = $this->route('video');

        $video = Video::find($video_id);

        return [
            'from' => [
                'required',
                "lt:{$video->duration}",
                Rule::unique('chapters')->where(function ($query) use($video_id) {
                    return $query->where('video_id', $video_id)
                        ->where('from', request()->get('from'));
                })
            ],
            'title' => 'required',
        ];
    }
}
