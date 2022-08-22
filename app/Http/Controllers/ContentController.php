<?php

namespace App\Http\Controllers;

use App\Http\Resources\Content\ContentResource;
use App\Http\Resources\Form\FormResource;
use App\Mail\GlobalMail;
use App\Models\Content;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $contents = Content::paginate();

        return ContentResource::collection($contents);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'page' => ['required', 'string'],
            'content' => ['required', 'array'],
        ]);

        $content = new Content();
        $content->page = $validatedData['page'];
        $content->content = $validatedData['content'];
        $content->save();

        return ContentResource::make($content);
    }

    public function show($idOrPage)
    {
        $content = Content::idOrPage($idOrPage)->firstOrFail();

        return ContentResource::make($content);
    }

}
