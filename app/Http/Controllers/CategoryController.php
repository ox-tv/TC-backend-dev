<?php

namespace App\Http\Controllers;

use App\Http\Resources\Category\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::active()->get();

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $category = new Category();

        $categoryName = $request->get('name');

        $category->name = $categoryName;
        $category->slug = Str::slug($categoryName);
        $category->status = $request->get('status', Category::STATUS_ACTIVE);

        $category->save();

        return CategoryResource::make($category);
    }

    public function show($idOrSlug)
    {
        $category = Category::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->firstOrFail();

        return CategoryResource::make($category);
    }

    public function update(Request $request, Category $category)
    {
        $categoryName = $request->get('name');

        $category->name = $categoryName;
        $category->slug = Str::slug($categoryName);
        $category->status = $request->get('status', $category->status);

        $category->save();

        return CategoryResource::make($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return CategoryResource::make($category);
    }
}
