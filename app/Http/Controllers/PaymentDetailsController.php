<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentDetails\PaymentDetailsResource;
use App\Models\PaymentDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentDetailsController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentDetails::query();

        $filters = $request->get('filters', []);
        $onlyArchivedFilter = Arr::get($filters, 'only_archived');
        $statusFilter = Arr::get($filters, 'status');
        $searchFilter = Arr::get($filters, 'search');

        if ($onlyArchivedFilter){
            $query->archived();
        }else{
            $query->nonArchived();
        }

        if ($statusFilter){
            $query->statusText($statusFilter);
        }

        if ($searchFilter){
            $query->whereHas('user', function ($q) use($searchFilter){
                $q->searchUsername($searchFilter);
            });
        }

        $sort = $request->get('sort');
        if ($sort == 'oldest'){
            $query->orderBy('created_at', 'ASC');
        }else{
            $query->orderBy('created_at', 'DESC');
        }

        $paymentDetails = $query->paginate();

        $paymentDetails->load(['user'])->append(['proof_code', 'eth_address']);

        return PaymentDetailsResource::collection($paymentDetails);
    }

    public function addressHistory(Request $request, User $user = null)
    {
        if (!$request->is('api/admin/*')){
            $user = auth('api')->user();
        }

        $paymentDetails = $user->paymentDetails()->verified()->latest()->paginate();

        $paymentDetails->append('eth_address');

        return PaymentDetailsResource::collection($paymentDetails);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();

        if ($user->paymentDetails()->nonArchived()->onGoing()->count() > 0){
            return response()->json(['message' => 'You already have ongoing request.'], 422);
        }

        $lastPaymentDetails = $user->paymentDetails()->verified()->latest()->first();

        $request->validate([
            'first_name' => [Rule::requiredIf(!$lastPaymentDetails)],
            'last_name' => [Rule::requiredIf(!$lastPaymentDetails)],
            'street_address' => ['required'],
            'street_number' => ['required'],
            'postal_code' => ['required'],
            'city' => ['required'],
            'country' => ['required'],
            'company_name' => ['nullable'],
            'vat_number' => ['nullable'],
            'eth_address' => 'required|regex:/^0x[a-fA-F0-9]{40}$/',
        ]);

        $user->eth_address = $request->get('eth_address');
        $user->save();

        $newPaymentDetails = new PaymentDetails();
        $newPaymentDetails->user_id = $user->id;
        $newPaymentDetails->status = PaymentDetails::STATUS_NEW;
        $newPaymentDetails->last_status_at = Carbon::now();
        $newPaymentDetails->proof_code = Str::upper(Str::random(16));

        $newPaymentDetails->first_name = $lastPaymentDetails->first_name?? $request->get('first_name');
        $newPaymentDetails->last_name = $lastPaymentDetails->last_name?? $request->get('last_name');
        $newPaymentDetails->street_address = $request->get('street_address');
        $newPaymentDetails->street_number = $request->get('street_number');
        $newPaymentDetails->postal_code = $request->get('postal_code');
        $newPaymentDetails->city = $request->get('city');
        $newPaymentDetails->country = $request->get('country');
        $newPaymentDetails->company_name = $request->get('company_name');
        $newPaymentDetails->vat_number = $request->get('vat_number');
        $newPaymentDetails->eth_address = $request->get('eth_address');

        $newPaymentDetails->save();

        return response()->json(['status' => 'ok']);
    }

    public function verifyPaymentDetails(Request $request)
    {
        $user = auth('api')->user();
        $paymentDetails = $user->paymentDetails()->nonArchived()->status(PaymentDetails::STATUS_CODE_SENT)->firstOrFail();

        $request->validate([
            'proof_code' => [
                'required', 'string', 'size:16',
                function ($attribute, $value, $fail) use($paymentDetails) {
                    if ($value && strlen($value) == 16 && $paymentDetails->proof_code != $value) {
                        $fail('The '.$attribute.' is invalid.');
                    }
                },
            ]
        ]);

        $paymentDetails->status = PaymentDetails::STATUS_VERIFIED;
        $paymentDetails->last_status_at = Carbon::now();
        $paymentDetails->save();

        return response()->json(['status' => 'ok']);
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'status' => ['required',Rule::in(PaymentDetails::STATUS_TEXT)],
            'ids' => ['required'],
            'ids.*' => ['required', Rule::exists('payment_details','id')]
        ]);

        $updateData = [
            'status' => array_flip(PaymentDetails::STATUS_TEXT)[$request->get('status')],
            'last_status_at' => Carbon::now()
        ];

        if ($updateData['status'] == PaymentDetails::STATUS_CODE_SENT){
            $updateData['code_sent_at'] = Carbon::now();
        }

        PaymentDetails::whereIn('id', $request->get('ids'))->update($updateData);

        return response()->json(['status' => 'ok']);
    }

    public function markAsArchive(Request $request)
    {
        $request->validate([
            'ids' => ['required'],
            'ids.*' => ['required', Rule::exists('payment_details','id')]
        ]);

        PaymentDetails::whereIn('id', $request->get('ids'))->update([
            'is_archive' => true,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function markAsNonArchive(Request $request)
    {
        $request->validate([
            'ids' => ['required'],
            'ids.*' => ['required', Rule::exists('payment_details','id')]
        ]);

        PaymentDetails::whereIn('id', $request->get('ids'))->update([
            'is_archive' => false,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
