<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentDetails\PaymentDetailsResource;
use App\Libraries\IdenfyClient;
use App\Models\PaymentDetails;
use App\Models\User;
use App\Models\UserMeta;
use App\Repository\Eloquent\TagRepository;
use App\Services\_2FAService;
use App\Services\EmailVerificationService;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
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

        if ($onlyArchivedFilter){
            $query->archived();
        }

        $paymentDetails = $query->paginate();

        $paymentDetails->load(['user'])->append(['proof_code', 'eth_address']);

        return PaymentDetailsResource::collection($paymentDetails);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();

        if ($user->paymentDetails()->hasOnGoing()->count() > 0){
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

        return $newPaymentDetails;
    }


}
