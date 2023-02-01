<?php

namespace App\Http\Controllers;

use App\Http\Resources\Feedback\FeedbackResource;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->get('filters', []);
        $typeFilter = Arr::get($filters, 'type');
        $locationFilter = Arr::get($filters, 'location');
        $fromValueFilter = Arr::get($filters, 'from_value');
        $toValueFilter = Arr::get($filters, 'to_value');
        $userIdFilter = Arr::get($filters, 'user_id');
        $emailFilter = Arr::get($filters, 'email');
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        $query = Feedback::query();

        if ($typeFilter) {
            $query->type($typeFilter);
        }

        if ($locationFilter) {
            $query->location($locationFilter);
        }

        if ($userIdFilter) {
            $query->user($userIdFilter);
        }

        if ($emailFilter) {
            $query->email($emailFilter);
        }

        if ($fromValueFilter) {
            $query->where('value', '>=', $fromValueFilter);
        }

        if ($toValueFilter) {
            $query->where('value', '<=', $toValueFilter);
        }

        if ($fromFilter) {
            $query->where('created_at', '>=', $fromFilter);
        }

        if ($toFilter) {
            $query->where('created_at', '<=', $toFilter);
        }

        $query->orderBy('created_at','desc');

        $feedbacks = $query->paginate();

        $feedbacks->load(['user']);

        return FeedbackResource::collection($feedbacks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', Rule::in(Feedback::TYPE_TEXT)],
            'location' => 'required',
            'origin' => 'nullable|string',
            'email' => 'nullable|email',
            'value' => [
                Rule::requiredIf(in_array($request->type, [Feedback::TYPE_TEXT[Feedback::TYPE_STAR], Feedback::TYPE_TEXT[Feedback::TYPE_THUMB]])),
                'numeric'
            ],
            'text' => [
                Rule::requiredIf(in_array($request->type, [Feedback::TYPE_TEXT[Feedback::TYPE_DEFAULT]])),
            ]
        ]);

        $feedback = new Feedback();
        $feedback->location = $request->get('location');
        $feedback->type = $request->get('type');
        $feedback->text = $request->get('text');
        $feedback->value = $request->get('value');
        $feedback->user_id = auth('api')->id();
        $feedback->email = $request->get('email');
        $feedback->origin = $request->get('origin');
        $feedback->save();

        return FeedbackResource::make($feedback);
    }

    public function getLocations()
    {
        return Feedback::select('location')->distinct()->pluck('location');
    }
}
