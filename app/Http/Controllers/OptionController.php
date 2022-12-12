<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OptionController extends Controller
{
    // Reasons
    public function setReasonsOption(Request $request, $key)
    {
        if (!in_array($key, Option::REASONS)){
            abort(404);
        }

        $request->validate([
            'reasons' => 'nullable|array',
            'reasons.*.key' => 'required|string',
            'reasons.*.value' => 'required|string',
            'reasons.*.status' => ['required', Rule::in([Option::REASONS_STATUS_ACTIVE, Option::REASONS_STATUS_INACTIVE])],
        ]);

        Option::set($key, json_encode($request->get('reasons')));

        return response()->json(["message" => "ok"]);
    }

    public function getReasonsOption(Request $request, $key)
    {
        if (!in_array($key, Option::REASONS)){
            abort(404);
        }

        $value = Option::get($key)->value ?? null;

        $value = $value? json_decode($value, true): null;

        if (!$request->is('api/admin/*') && is_array($value)){
            foreach ($value as $key => $reason) {
                if (empty($reason['status']) || $reason['status'] == Option::REASONS_STATUS_INACTIVE) {
                    unset($value[$key]);
                }
            }
        }

        return $value;
    }

    // Forbidden Words
    public function setForbiddenWords(Request $request)
    {
        $request->validate([
            'forbidden_words' => 'nullable|array',
            'forbidden_words.*' => 'nullable|string',
        ]);

        $forbiddenWords = $request->get('forbidden_words')?? [];

        Option::set(Option::FORBIDDEN_WORDS, json_encode($forbiddenWords));

        return response()->json(["message" => "ok"]);
    }

    public function getForbiddenWords(Request $request)
    {
        $forbiddenWords = Option::get(Option::FORBIDDEN_WORDS);

        return $forbiddenWords? json_decode($forbiddenWords->value, true) : [];
    }

    public function getAdSpace(Request $request){
        $adSpaces = Option::get(Option::AD_SPACES);

        return $adSpaces? json_decode($adSpaces->value, true) : [];
    }

    public function setAdSpace(Request $request){

        $adImageUrl = $request->get('url');
        $adSpaceKey = $request->get('key');

        $adSpacesOption = Option::get(Option::AD_SPACES) ?? [];

        $adSpaces = json_decode($adSpacesOption->value, true);

        if($adImageUrl){
            $adSpaces[$adSpaceKey] = $adImageUrl;
        }else{
            unset($adSpaces[$adSpaceKey]);
        }

        Option::set(Option::AD_SPACES, json_encode($adSpaces));

        return response()->json(["message" => "ok"]);

    }


}
