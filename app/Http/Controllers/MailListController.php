<?php

namespace App\Http\Controllers;


use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
use App\Http\Resources\Tag\TagResource;
use App\Models\MailList;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MailListController extends Controller
{
    public function index(Request $request)
    {
        $query = MailList::query();

        $filters = $request->get('filters', []);
        $locationFilter = Arr::get($filters, 'location');

        if ($locationFilter) {
            $query->location($locationFilter);
        }

        return $query->paginate();
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'location' => ['nullable'],
        ]);

        return MailList::updateOrCreate(
            ['email' => $request->get('email')],
            ['location' => $request->get('location')]
        );
    }

    public function getLocations()
    {
        return MailList::select('location')->distinct()->pluck('location');
    }
}
