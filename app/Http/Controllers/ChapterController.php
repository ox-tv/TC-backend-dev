<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chapter\ChapterStore;
use App\Http\Requests\Chapter\ChapterUpdate;
use App\Http\Resources\Chapter\ChapterItem;
use App\Models\Chapter;
use App\Models\Video;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function index(Request $request, $id_or_url_hash)
    {
        $video = Video::where(function ($query) use ($id_or_url_hash){
                $query->whereId($id_or_url_hash)->orWhere('url_hash', $id_or_url_hash);
            })
            ->where(function ($query){
                $query->where(function ($query){
                    $query->mine();
                })->orWhere('status', Video::STATUS_PUBLISHED);
            })->firstorFail();

        return ChapterItem::collection($video->chapters);
    }

    public function store(ChapterStore $request, $video_id)
    {
        $chapter = new Chapter();

        $chapter->from = $request->get('from');
        $chapter->title = $request->get('title');
        $chapter->video_id = $video_id;

        $chapter->save();

        return response()->json(['message' => 'ok']);
    }

    public function update(ChapterUpdate $request, $video_id, $chapter_id)
    {
        $chapter = Chapter::find($chapter_id);

        $chapter->from = $request->get('from');
        $chapter->title = $request->get('title');

        $chapter->save();

        return response()->json(['message' => 'ok']);
    }

    public function destroy($video_id, Chapter $chapter)
    {
        if(!$chapter->video()->mine()->exists()){
            abort(403, 'Permission denied');
        }

        $chapter->delete();

        return response()->json(['message' => 'ok']);
    }
}
