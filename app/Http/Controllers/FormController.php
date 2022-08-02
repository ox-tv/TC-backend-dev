<?php

namespace App\Http\Controllers;

use App\Http\Resources\Form\FormResource;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FormController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->get('filters', []);
        $typeFilter = Arr::get($filters, 'type');
        $userIdFilter = Arr::get($filters, 'user_id');
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        $query = Form::query();

        if ($typeFilter) {
            $query->type($typeFilter);
        }

        if ($userIdFilter) {
            $query->user($userIdFilter);
        }

        if ($fromFilter) {
            $query->where('created_at', '>=', $fromFilter);
        }

        if ($toFilter) {
            $query->where('created_at', '<=', $toFilter);
        }

        $query->orderBy('created_at','desc');

        $forms = $query->paginate();

        $forms->load(['user']);

        return FormResource::collection($forms);
    }

    public function storeContactUs(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'subject' => ['required', 'string'],
            'message' => ['required'],
        ]);

        $form = new Form();
        $form->type = Form::TYPE_CONTACT_US;
        $form->data = $validatedData;
        $form->user_id = auth('api')->id();
        $form->save();

        return FormResource::make($form);
    }


}
