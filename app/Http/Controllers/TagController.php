<?php

namespace App\Http\Controllers;


use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
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

    public function store(TagStore $request)
    {
        $tag = new Tag();
        $tag->name = $request->get('name');
        $tag->status = array_flip(Tag::STATUS_TEXT)[$request->get('status')]?? Tag::STATUS_PUBLISHED;
        $tag->creation_scope = Tag::CREATION_SCOPE_ADMIN;
        $tag->save();

        return new TagItem($tag);
    }

    public function update(TagUpdate $request, $tag_id)
    {
        $tag = Tag::findorFail($tag_id);
        $tag->name = $request->get('name');
        $tag->status = array_flip(Tag::STATUS_TEXT)[$request->get('status')]?? Tag::STATUS_PUBLISHED;
        $tag->save();

        return new TagItem($tag);
    }

    public function destroy($tag_id)
    {
        $tag = Tag::findorFail($tag_id);

        // remove tag relations
        $tag->videos()->sync([]);
        $tag->favoritedByUsers()->sync([]);

        $tag->delete();

        return response()->json(['message' => 'ok']);
    }
}
