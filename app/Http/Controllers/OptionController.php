<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionController extends Controller
{
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

}
