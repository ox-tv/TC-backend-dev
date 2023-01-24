<?php

namespace App\Repository\Eloquent;

use App\Models\Tag;
use App\Models\TagUser;
use App\Models\TagVideo;
use Illuminate\Support\Facades\DB;
use Throwable;

class TagRepository
{
    public function store($data): Tag
    {
        return Tag::firstOrCreate(
            ['name' => strtolower($data['name'])],
            [
                'status' => $data['status'],
                'creation_scope' => $data['creation_scope']
            ]
        );
    }

    public function update($tagId, $data): Tag
    {
        $tag = Tag::findOrFail($tagId);
        $tag->name = strtolower($data['name']);
        $tag->status = $data['status'] ?? $tag->status;
        $tag->save();

        return $tag;
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

    public function getById($tagId, $throwOnFail = true): Tag
    {
        return $throwOnFail? Tag::findOrFail($tagId) : Tag::find($tagId);
    }
}
