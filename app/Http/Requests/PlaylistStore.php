<?php

namespace App\Http\Requests;

use App\Models\Playlist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlaylistStore extends FormRequest
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
            'name' => 'required',
            'user_id' => 'sometimes|exists:users,id',
            'status' => ['nullable', Rule::in(Playlist::STATUS_TEXT)],
        ];
    }

}
