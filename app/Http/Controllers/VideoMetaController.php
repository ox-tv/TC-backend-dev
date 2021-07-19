<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoMetaController extends Controller
{
    public function getLayers(Request $request, $idOrHash)
    {
        $video_query = Video::where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        });

        if (!$request->is('api/admin/*')){
            $video_query->where(function ($query){
                $query->where(function ($query){
                    $query->mine();
                })->orWhere('status', Video::STATUS_PUBLISHED);
            });
        }

        $video = $video_query->firstOrFail();

        return $video->meta()->where('key', 'layers')->first()->value?? null;
    }

    public function setLayers(Request $request, $idOrHash)
    {
        $request->validate([
            'layers' => ['required'],
        ]);

        $video = Video::where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        })->mine()->firstOrFail();

        $video->meta()->updateOrCreate(
            ['key' => 'layers'],
            ['value' => $request->get('layers')]
        );

        return response()->json(['message' => 'ok']);
    }
}
