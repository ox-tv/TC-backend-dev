<?php

namespace App\Repository\Eloquent;

use App\Models\Category;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CategoryRepository
{
    public function store($data): Category
    {
        return Category::firstOrCreate(
            ['slug' => $data['slug'] ?? Str::slug($data['name'])],
            [
                'name' => $data['name'],
                'status' => $data['status'],
            ]
        );
    }

    public function update($id, $data): Category
    {
        $category = Category::findOrFail($id);
        $category->name = $data['name'];
        $category->slug = $data['slug'] ?? Str::slug($data['name']);
        $category->status = $data['status'] ?? $category->status;
        $category->save();

        return $category;
    }

    public function destroy($id): bool
    {
        try {
            DB::beginTransaction();

            // Remove relations
            Video::where('category_id', $id)->update(['category_id' => null]);

            // Remove Category
            Category::where('id', $id)->delete();

            DB::commit();
            return true;

        } catch (Throwable $e) {

            DB::rollback();
            return false;
        }
    }

    public function getById($id, $throwOnFail = true): Category
    {
        return $throwOnFail? Category::findOrFail($id) : Category::find($id);
    }

    public function getByIdOrSlug($idOrSlug, $throwOnFail = true): Category
    {
        $query = Category::when(is_numeric($idOrSlug), function ($q) use ($idOrSlug){
            $q->where('id', $idOrSlug);
        })->when(!is_numeric($idOrSlug), function ($q) use ($idOrSlug){
            $q->where('slug', $idOrSlug);
        });

        return $throwOnFail? $query->firstOrFail() : $query->first();
    }
}
