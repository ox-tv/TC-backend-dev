<?php

namespace App\Http\Controllers;


use App\Http\Requests\Tag\TagStore;
use App\Http\Requests\Tag\TagUpdate;
use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\Tag\TagResource;
use App\Models\Company;
use App\Models\Tag;
use App\Repository\Eloquent\TagRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query();

        $companies = $query->paginate();

        return CompanyResource::collection($companies);
    }

    public function show(Request $request, $companyId)
    {
        $company = Company::where('id', $companyId)->firstOrFail();

        return CompanyResource::make($company);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'avatar_url' => ['nullable', 'url'],
            'vat_number' => ['required'],
            'vat_rate' => ['required', 'numeric'],
            'street_address' => ['required'],
            'street_no' => ['required'],
            'postal_code' => ['required'],
            'city' => ['required'],
            'country' => ['required'],
            'contact_person_name' => ['required'],
            'contact_person_email' => ['required'],
            'contact_person_phone' => ['nullable'],
            'invocing_questions_email' => ['required'],
        ]);

        $company = new Company();
        $company->name = $request->get('name');
        $company->avatar_url = $request->get('avatar_url');

        $company->vat_number = $request->get('vat_number');
        $company->vat_rate = $request->get('vat_rate');

        $company->street_address = $request->get('street_address');
        $company->street_no = $request->get('street_no');
        $company->postal_code = $request->get('postal_code');
        $company->city = $request->get('city');
        $company->country = $request->get('country');

        $company->contact_person_name = $request->get('contact_person_name');
        $company->contact_person_email = $request->get('contact_person_email');
        $company->contact_person_phone = $request->get('contact_person_phone');
        $company->invocing_questions_email = $request->get('invocing_questions_email');

        $company->save();

        return CompanyResource::make($company);
    }

    public function update(Request $request, $companyId)
    {
        $request->validate([
            'name' => ['required'],
            'avatar_url' => ['nullable', 'url'],
            'vat_number' => ['required'],
            'vat_rate' => ['required', 'numeric'],
            'street_address' => ['required'],
            'street_no' => ['required'],
            'postal_code' => ['required'],
            'city' => ['required'],
            'country' => ['required'],
            'contact_person_name' => ['required'],
            'contact_person_email' => ['required'],
            'contact_person_phone' => ['nullable'],
            'invocing_questions_email' => ['required'],
        ]);

        $company = Company::where('id', $companyId)->firstOrFail();

        $company->name = $request->get('name');
        $company->avatar_url = $request->get('avatar_url');

        $company->vat_number = $request->get('vat_number');
        $company->vat_rate = $request->get('vat_rate');

        $company->street_address = $request->get('street_address');
        $company->street_no = $request->get('street_no');
        $company->postal_code = $request->get('postal_code');
        $company->city = $request->get('city');
        $company->country = $request->get('country');

        $company->contact_person_name = $request->get('contact_person_name');
        $company->contact_person_email = $request->get('contact_person_email');
        $company->contact_person_phone = $request->get('contact_person_phone');
        $company->invocing_questions_email = $request->get('invocing_questions_email');

        $company->save();

        return CompanyResource::make($company);
    }

    public function destroy($companyId)
    {
        $company = Company::where('id', $companyId)->firstOrFail();

        $company->delete();

        return response()->json(['message' => 'ok']);
    }
}
