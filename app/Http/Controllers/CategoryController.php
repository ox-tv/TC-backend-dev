<?php

namespace App\Http\Controllers;

use App\Http\Resources\Category\CategoryResource;
use App\Models\Category;
use App\Repository\Eloquent\CategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function index()
    {
        $categories = Category::active()->get();

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $category = $this->categoryRepository->store([
            'name' => $request->get('name'),
            'slug' => Str::slug($request->get('name')),
            'status' => $request->get('status', Category::STATUS_ACTIVE),
        ]);

        return CategoryResource::make($category);
    }

    public function show($idOrSlug): CategoryResource
    {
        $category = $this->categoryRepository->getByIdOrSlug($idOrSlug);

        return CategoryResource::make($category);
    }

    public function update(Request $request, $id)
    {
        $category = $this->categoryRepository->update($id, [
            'name' => $request->get('name'),
            'slug' => Str::slug($request->get('name')),
            'status' => $request->get('status'),
        ]);

        return CategoryResource::make($category);
    }

    public function destroy($id)
    {
        $result = $this->categoryRepository->destroy($id);

        return $result? response()->json(['message' => 'ok']) : abort(500, 'Somethins is wrong.');
    }
}
