<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionController extends Controller
{

    public function report_reasons_store(Request $request)
    {
        $is_video = $request->is('api/admin/options/report/video/reasons');
        $is_comment = $request->is('api/admin/options/report/comment/reasons');

        if($is_video){
            $option = Option::where("key", "report_video_reasons")->first();
        }

        if($is_comment){
            $option = Option::where("key", "report_comment_reasons")->first();
        }

        if(!$option){
            $option = new Option();
        }

        if($is_video){
            $option->key = "report_video_reasons";
        }

        if($is_comment){
            $option->key = "report_comment_reasons";
        }

        $option->value = json_encode($request->get('reasons'));

        $option->save();

        return response()->json(["message" => "ok"]);
    }

    public function report_video_reasons_show()
    {
        return Option::where("key", "report_video_reasons")->first()->value ?? [];
    }

    public function report_comment_reasons_show()
    {
        return Option::where("key", "report_comment_reasons")->first()->value ?? [];
    }

}
