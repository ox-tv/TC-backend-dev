<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chapter\ChapterStore;
use App\Http\Requests\Chapter\ChapterUpdate;
use App\Http\Resources\Chapter\ChapterItem;
use App\Http\Resources\Language\LanguageItem;
use App\Models\Chapter;
use App\Models\Language;
use App\Models\Video;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(Request $request)
    {
        $languages = Language::orderBy('order', 'desc')
            ->orderBy('id','ASC')
            ->get();

        return LanguageItem::collection($languages);
    }
}
