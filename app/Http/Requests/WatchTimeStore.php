<?php

namespace App\Http\Requests;

use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WatchTimeStore extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('api')->check() || !empty($this->request->get('session_id'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_time' => intval($this->request->get('start_time')),
            'end_time' => intval($this->request->get('end_time')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_time' => ['required', "numeric", "lt:end_time"],
            'end_time' => ['required', "numeric"],
            'session_id' => ['nullable', "string", "min:12"],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator)
        {
            $userID = auth('api')->check()? auth('api')->id(): $this->get('session_id');
            $duration = $this->get('end_time') - $this->get('start_time') - 1;

            if ($duration > 50){
                $validator->errors()->add('duration', 'Watch time duration is too long.');
            }

            $lastWatchTimeUpdatedAT = Cache::get("watchtime_user{$userID}_last_updated_at");

            if(!$lastWatchTimeUpdatedAT){
                return;
            }

            if ($lastWatchTimeUpdatedAT >= Carbon::now()->subSeconds($duration - 1)->format('Y-m-d H:i:s')) {
                $validator->errors()->add('watch_time', 'Your watch time duration is bigger than datetime of last submitted watch time record.');
            }
        });
    }
}
