<?php

namespace App\Http\Controllers;

use App\Http\Resources\Subtitle\SubtitleItem;
use App\Models\Language;
use App\Models\Subtitle;
use App\Models\Video;
use Carbon\Carbon;
use Done\Subtitles\Subtitles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SubtitleController extends Controller
{
    public function getSubtitles(Request $request, $videoIdOrHash)
    {
        $video_query = Video::where(function ($query) use ($videoIdOrHash){
            $query->whereId($videoIdOrHash)->orWhere('url_hash', $videoIdOrHash);
        });

        if (!$request->is('api/admin/*')){
            $video_query->where(function ($query){
                $query->where(function ($query){
                    $query->mine();
                })->orWhere('status', Video::STATUS_PUBLISHED);
            });
        }

        $video = $video_query->firstOrFail();

        $subtitles = Subtitle::where('video_id', $video->id)->get();

        return SubtitleItem::collection($subtitles);
    }

    public function store(Request $request, $videoIdOrHash)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,srt,vtt|max:256',
            'language_id' => ['required','exists:languages,id'],
        ]);

        $video_query = Video::where(function ($query) use ($videoIdOrHash){
            $query->whereId($videoIdOrHash)->orWhere('url_hash', $videoIdOrHash);
        });

        if (!$request->is('api/admin/*')){
            $video_query->mine();
        }

        $video = $video_query->firstOrFail();

        $language = Language::find($request->get('language_id'));

        // upload
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileName = $language->code . '.' . $extension;
        $folder = 'subtitles/' . $video->url_hash;

        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        $path = Storage::disk('public')->putFileAs($folder, $file, $fileName);

        if(!$path) {
            return response()->json(['message' => 'something wrong!'], 500);
        }

        if(strtolower($extension) != 'vtt'){
            $originalPath = $path;
            $path = substr($path, 0, -1 * strlen($file->extension())) . 'vtt';

            Subtitles::convert(
                Storage::disk('public')->path($originalPath),
                Storage::disk('public')->path($path)
            );
        }

        $subtitle = Subtitle::UpdateOrCreate([
                'video_id' => $video->id,
                'language_id' => $language->id
            ],[
                'file_path' => $path,
                'original_path' => $originalPath?? null
            ]
        );

        return SubtitleItem::make($subtitle);
    }

    public function destroy(Request $request, Subtitle $subtitle)
    {
        $video_query = Video::where('id', $subtitle->video_id);

        if (!$request->is('api/admin/*')){
            $video_query->mine();
        }

        $video = $video_query->firstOrFail();

        abort_unless(Storage::disk('public')->exists($subtitle->file_path), 404, 'File not found');

        Storage::disk('public')->delete($subtitle->file_path);

        $subtitle->delete();

        return response()->json(['message' => 'ok']);
    }
}
