<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $directory = 'subtitles/' . $video->url_hash;

        $files = Storage::disk('public')->files($directory);

        return $files;
    }

    public function store(Request $request, $videoIdOrHash)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,srt,vtt|max:256',
            'lang' => 'required',
        ]);

        $video_query = Video::where(function ($query) use ($videoIdOrHash){
            $query->whereId($videoIdOrHash)->orWhere('url_hash', $videoIdOrHash);
        });

        if (!$request->is('api/admin/*')){
            $video_query->mine();
        }

        $video = $video_query->firstOrFail();

        // upload
        $file = $request->file('file');
        $fileName = $request->get('lang') . '.srt';
        $folder = 'subtitles/' . $video->url_hash;

        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        $path = Storage::disk('public')->putFileAs($folder, $file, $fileName);

        if(!$path) {
            return response()->json(['message' => 'something wrong!'], 500);
        }

        return Storage::url($path);
    }

    public function destroy(Request $request, $videoIdOrHash, $fileName)
    {
        $video_query = Video::where(function ($query) use ($videoIdOrHash){
            $query->whereId($videoIdOrHash)->orWhere('url_hash', $videoIdOrHash);
        });

        if (!$request->is('api/admin/*')){
            $video_query->mine();
        }

        $video = $video_query->firstOrFail();

        $directory = 'subtitles/' . $video->url_hash;
        $filePath = $directory . '/' . $fileName;

        abort_unless(Storage::disk('public')->exists($filePath), 404, 'File not found');

        Storage::disk('public')->delete($filePath);

        return response()->json(['message' => 'ok']);
    }
}
