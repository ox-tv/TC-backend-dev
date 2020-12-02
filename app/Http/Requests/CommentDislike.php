<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentDislike extends FormRequest
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
        $comment = $this->route('comment');

        $validator->after(function ($validator)use ($user, $comment) {
            if($comment->dislikedBy()->where('user_id', $user->id)->first()){
                $validator->errors()->add('comment', 'comment.validation.already_disliked');
            }
        });
    }

}
