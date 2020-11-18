<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryItem;
use App\Http\Resources\CategoryCollection;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return CategoryCollection
     */
    public function index()
    {

        $categories = Category::active()->get();

        return new CategoryCollection($categories);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return CategoryItem
     */
    public function store(Request $request)
    {
        $category = new Category();

        $category->name = $request->get('name');
        $category->status = $request->get('status', Category::STATUS_ACTIVE);

        $category->save();

        return new CategoryItem($category);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $category
     * @return CategoryItem
     */
    public function update(Request $request, Category $category)
    {

        $category->name = $request->get('name');

        $category->status = $request->get('status', $category->status);

        $category->save();

        return new CategoryItem($category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Category $category
     * @return CategoryItem
     * @throws \Exception
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return new CategoryItem($category);
    }
}
