<?php

namespace App\Http\Controllers;


use App\Http\Resources\Tag\TagItem;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tag::query();

        if (!$request->is('api/admin/*')){
            $query->published();
        }

        $filters = $request->get('filters', []);

        $searchFilter = Arr::get($filters, 'search');

        if($searchFilter){
            $query->searchName($searchFilter);
        }

        return TagItem::collection($query->paginate());
    }
}
