<?php

namespace App\Http\Controllers;

use App\Http\Resources\Content\ContentResource;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->get('filters', []);
        $keyFilter = Arr::get($filters, 'key');

        $query = Content::query();

        $keyFilter && $query->idOrKey($keyFilter);

        $contents = $query->paginate();

        $contents->load(['lastModifiedBy'])->append(['status_text']);

        return ContentResource::collection($contents);
    }

    public function show(Request $request, $idOrKey)
    {
        $query = Content::idOrKey($idOrKey);

        if (!$request->is('api/admin/*')){
            $query->status(Content::STATUS_PUBLISHED);
        }

        $content = $query->first();

        if ($content && $request->is('api/admin/*')){
            $content->load(['lastModifiedBy'])->append(['status_text']);
        }

        return $content? ContentResource::make($content) : null;
    }

    public function store(Request $request)
    {
        $request->validate([
            'key' => ['required', 'string'],
            'content' => ['required', 'array'],
            'status' => ['required', Rule::in(Content::STATUS_TEXT)],
        ]);

        if ($request->get('status') == Content::STATUS_TEXT[Content::STATUS_PUBLISHED]){
            Content::where('key', $request->get('key'))->update(['status' => Content::STATUS_DRAFT]);
        }

        $content = new Content();
        $content->key = $request->get('key');
        $content->content = $request->get('content');
        $content->status = $request->get('status');
        $content->last_modified_by = auth('api')->id();
        $content->save();

        $content->load(['lastModifiedBy'])->append(['status_text']);

        return ContentResource::make($content);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'key' => ['required', 'string'],
            'content' => ['required', 'array'],
            'status' => ['required', Rule::in(Content::STATUS_TEXT)],
        ]);

        if ($request->get('status') == Content::STATUS_TEXT[Content::STATUS_PUBLISHED]){
            Content::where('key', $request->get('key'))->update(['status' => Content::STATUS_DRAFT]);
        }

        $content = Content::findOrFail($id);
        $content->key = $request->get('key');
        $content->content = $request->get('content');
        $content->status = $request->get('status');
        $content->last_modified_by = auth('api')->id();
        $content->save();

        $content->load(['lastModifiedBy'])->append(['status_text']);

        return ContentResource::make($content);
    }

    public function destroy($id)
    {
        Content::where('id', $id)->delete();

        return response()->json(['status' => 'ok']);
    }
}
