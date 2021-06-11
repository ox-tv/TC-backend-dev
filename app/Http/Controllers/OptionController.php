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
        $key = '';

        if($request->is('api/admin/options/report/video/reasons')){
            $key = 'report_video_reasons';
        }

        if($request->is('api/admin/options/report/comment/reasons')){
            $key = 'report_comment_reasons';
        }

        if($request->is('api/admin/options/video/hide/reasons')){
            $key = 'video_hide_reasons';
        }

        if($request->is('api/admin/options/video/delete/reasons')){
            $key = 'video_delete_reasons';
        }

        if($request->is('api/admin/options/comment/delete/reasons')){
            $key = 'comment_delete_reasons';
        }

        $option = Option::where("key", $key)->first();

        if(!$option){
            $option = new Option();
            $option->key = $key;
        }

        $option->value = json_encode($request->get('reasons'));

        $option->save();

        return response()->json(["message" => "ok"]);
    }

    public function reasons_show(Request $request)
    {
        $key = '';

        if($request->is('api/options/report/video/reasons')){
            $key = 'report_video_reasons';
        }

        if($request->is('api/options/report/comment/reasons')){
            $key = 'report_comment_reasons';
        }

        if($request->is('api/options/video/hide/reasons')){
            $key = 'video_hide_reasons';
        }

        if($request->is('api/options/video/delete/reasons')){
            $key = 'video_delete_reasons';
        }

        if($request->is('api/options/comment/delete/reasons')){
            $key = 'comment_delete_reasons';
        }

        return Option::where("key", $key)->first()->value ?? [];
    }

}
