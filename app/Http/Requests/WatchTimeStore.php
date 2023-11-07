<?php

namespace App\Http\Requests;

use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
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
            'start_time' => ['required', "numeric", "lt:end_time"],
            'end_time' => ['required', "numeric"],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $user = auth('api')->user();
            //$idOrUrlHash = $this->route('idOrUrlHash');
            //$video = Video::published()->where('id', $idOrUrlHash)->orWhere('url_hash', $idOrUrlHash)->firstOrFail();
            $duration = $this->get('end_time') - $this->get('start_time') - 1;

            if ($duration > 32){
                $validator->errors()->add('duration', 'Watch time duration is too long.');
            }

            $lastWatchTime = DB::table('watch_times')
                ->where('user_id', $user->id)
                //->where('video_id', $video->id)
                ->orderByDesc('created_at')
                ->first();

            if(!$lastWatchTime){
                return;
            }

            if ($lastWatchTime->created_at >= Carbon::now()->subSeconds($duration)->format('Y-m-d H:i:s')) {
                $validator->errors()->add('watch_time', 'Your watch time duration is bigger than datetime of last submitted watch time record.');
            }
        });
    }
}
