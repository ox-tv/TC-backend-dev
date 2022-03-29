<?php

namespace App\Http\Controllers;

use App\Http\Resources\Language\LanguageResource;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(Request $request)
    {
        $languages = Language::orderBy('order', 'desc')
            ->orderBy('id','ASC')
            ->get();

        return LanguageResource::collection($languages);
    }
}
