<?php

namespace App\Repository\Eloquent;

use App\Models\Feedback;
use App\Models\Tag;
use App\Models\TagUser;
use App\Models\TagVideo;
use Illuminate\Support\Facades\DB;
use Throwable;

class FeedbackRepository
{
    public function store($data): Feedback
    {
        $data = $this->filterData($data);

        $feedback = new Feedback();

        foreach ($data as $key => $value){
            $feedback->{$key} = $value;
        }

        $feedback->save();

        return $feedback;
    }

    public function update($feedbackId, $data): Feedback
    {
        $data = $this->filterData($data);

        $feedback = Feedback::findOrFail($feedbackId);

        foreach ($data as $key => $value){
            $feedback->{$key} = $value;
        }

        $feedback->save();

        return $feedback;
    }

    public function destroy($tagId): bool
    {
        try {
            DB::beginTransaction();

            // Remove tag relations
            TagVideo::where('tag_id', $tagId)->delete();
            TagUser::where('tag_id', $tagId)->delete();

            // Remove tag
            Tag::where('id', $tagId)->delete();

            DB::commit();
            return true;

        } catch (Throwable $e) {

            DB::rollback();
            return false;
        }
    }

    public function getById($feedbackId, $throwOnFail = true): Feedback
    {
        return $throwOnFail? Feedback::findOrFail($feedbackId) : Tag::find($feedbackId);
    }

    private function filterData($data): array
    {
        $allowedKeys = [
            'location',
            'type',
            'value',
            'text',
            'user_id',
            'origin',
        ];
        return array_filter($data, function($v, $k) use ($allowedKeys) {
            return in_array($k, $allowedKeys);
        }, ARRAY_FILTER_USE_BOTH);
    }
}
