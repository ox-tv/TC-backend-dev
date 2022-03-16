<?php

namespace App\Http\Controllers;

use App\Http\Resources\Video\VideoMetaResource;
use App\Models\Option;
use App\Models\Playlist;
use App\Models\Video;
use App\Models\VideoMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'layers' => ['nullable'],
        ]);

        $video = Video::where(function ($query) use ($idOrHash){
            $query->whereId($idOrHash)->orWhere('url_hash', $idOrHash);
        })->mine()->firstOrFail();

        $video->meta()->updateOrCreate(
            ['key' => 'layers'],
            ['value' => json_encode($request->get('layers'))]
        );

        return response()->json(['message' => 'ok']);
    }

    public function storeMeta(Request $request, $videoIdOrHash, $key)
    {
        $request->validate([
            'value' => 'nullable|array',
        ]);

        $video = Video::where(function ($query) use ($videoIdOrHash){
            $query->whereId($videoIdOrHash)->orWhere('url_hash', $videoIdOrHash);
        })->mine()->firstOrFail();

        if (in_array($key, VideoMeta::KEYS_WITH_JSON_VALUE)){
            $video->meta()->updateOrCreate(
                ['key' => $key],
                ['value' => json_encode($request->get('value'))]
            );
        }else{
            $video->meta()->updateOrCreate(
                ['key' => $key],
                ['value' => $request->get('value')]
            );
        }

        return response()->json(["message" => "ok"]);
    }

    public function get(Request $request, $idOrHash, $key)
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

        return VideoMetaResource::make($video->meta()->where('key', $key)->first());
    }
}
