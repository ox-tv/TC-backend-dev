<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionController extends Controller
{
    // Get Reasons
    public function reportVideoReasonsGet()
    {
        return $this->getSingleMode(Option::KEY_REPORT_VIDEO_REASONS);
    }

    public function reportCommentReasonsGet()
    {
        return $this->getSingleMode(Option::KEY_REPORT_COMMENT_REASONS);
    }

    public function hideVideoReasonsGet()
    {
        return $this->getSingleMode(Option::KEY_HIDE_VIDEO_REASONS);
    }

    public function deleteVideoReasonsGet()
    {
        return $this->getSingleMode(Option::KEY_DELETE_VIDEO_REASONS);
    }

    public function deleteCommentReasonsGet()
    {
        return $this->getSingleMode(Option::KEY_DELETE_COMMENT_REASONS);
    }

    public function rejectPublisherRequestReasonsGet()
    {
        return $this->getSingleMode(Option::KEY_REJECT_PUBLISHER_REQUEST_REASONS);
    }


    // Store Reasons
    public function reportVideoReasonsStore(Request $request)
    {
        return $this->storeSingleMode(
            Option::KEY_REPORT_VIDEO_REASONS,
            json_encode($request->get('reasons'))
        );
    }

    public function reportCommentReasonsStore(Request $request)
    {
        return $this->storeSingleMode(
            Option::KEY_REPORT_COMMENT_REASONS,
            json_encode($request->get('reasons'))
        );
    }

    public function hideVideoReasonsStore(Request $request)
    {
        return $this->storeSingleMode(
            Option::KEY_HIDE_VIDEO_REASONS,
            json_encode($request->get('reasons'))
        );
    }

    public function deleteVideoReasonsStore(Request $request)
    {
        return $this->storeSingleMode(
            Option::KEY_DELETE_VIDEO_REASONS,
            json_encode($request->get('reasons'))
        );
    }

    public function deleteCommentReasonsStore(Request $request)
    {
        return $this->storeSingleMode(
            Option::KEY_DELETE_COMMENT_REASONS,
            json_encode($request->get('reasons'))
        );
    }

    public function rejectPublisherRequestReasonsStore(Request $request)
    {
        return $this->storeSingleMode(
            Option::KEY_REJECT_PUBLISHER_REQUEST_REASONS,
            json_encode($request->get('reasons'))
        );
    }


    // Core Methods
    private function storeSingleMode($key, $value)
    {
        $option = Option::where("key", $key)->first();

        if(!$option){
            $option = new Option();
            $option->key = $key;
        }

        $option->value = $value;

        $option->save();

        return response()->json(["message" => "ok"]);
    }

    private function getSingleMode($key)
    {
        return Option::where("key", $key)->first()->value ?? null;
    }
}
