<?php

namespace App\Http\Controllers;


use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
use App\Http\Resources\Tag\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->is('api/admin/*');

        $query = Tag::query();

        if (!$request->is('api/admin/*')){
            $query->published();
        }

        $filters = $request->get('filters', []);
        $searchFilter = Arr::get($filters, 'search');
        $statusFilter = Arr::get($filters, 'status');
        $excludeIdsFilter = Arr::get($filters, 'exclude_ids');
        $excludeNamesFilter = Arr::get($filters, 'exclude_names');

        if($searchFilter){
            $query->searchName($searchFilter);
        }

        if($excludeIdsFilter){
            $query->whereNotIn('id', $excludeIdsFilter);
        }

        if($excludeNamesFilter){
            $query->whereNotIn('name', $excludeNamesFilter);
        }

        if($statusFilter && !empty(array_flip(Tag::STATUS_TEXT)[$statusFilter])){
            $query->where('status', array_flip(Tag::STATUS_TEXT)[$statusFilter]);
        }

        // Add Sorting
        $sort = $request->get('sort');
        if($sort === 'most_video'){
            $query->withCount('videos')->orderBy('videos_count', 'desc');
        }elseif ($sort === 'most_favorited'){
            $query->withCount('favoritedByUsers')->orderBy('favorited_by_users_count', 'desc');
        }

        $tags = $query->paginate();

        if ($isAdmin){
            $tags->append(['favorited_by_users_count', 'videos_count']);
        }

        return TagResource::collection($tags);
    }

    public function show(Request $request, $tag_id)
    {
        $isAdmin = $request->is('api/admin/*');

        $tag = Tag::findorFail($tag_id);

        if ($isAdmin){
            $tag->append(['favorited_by_users_count', 'videos_count']);
        }

        return new TagResource($tag);
    }

    public function store(TagStore $request)
    {
        $tag = new Tag();
        $tag->name = $request->get('name');
        $tag->status = array_flip(Tag::STATUS_TEXT)[$request->get('status')]?? Tag::STATUS_PUBLISHED;
        $tag->creation_scope = Tag::CREATION_SCOPE_ADMIN;
        $tag->save();

        return new TagResource($tag);
    }

    public function update(TagUpdate $request, $tag_id)
    {
        $tag = Tag::findorFail($tag_id);
        $tag->name = $request->get('name');
        $tag->status = array_flip(Tag::STATUS_TEXT)[$request->get('status')]?? Tag::STATUS_PUBLISHED;
        $tag->save();

        return new TagResource($tag);
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
