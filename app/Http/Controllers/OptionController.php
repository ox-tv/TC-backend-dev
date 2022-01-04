<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        ]);

        Option::set($key, json_encode($request->get('reasons')));

        return response()->json(["message" => "ok"]);
    }

    public function getReasonsOption($key)
    {
        if (!in_array($key, Option::REASONS)){
            abort(404);
        }

        return Option::get($key)->value ?? null;
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
}
