<?php

namespace App\Http\Requests;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanUpdate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required'],
            'interval' => ['required', 'numeric', 'gt:0'],
            'status' => ['required', Rule::in(Plan::STATUS_TEXT)],
            'description' => ['nullable'],
            'thumbnail' => ['nullable'],
            'rates.*.payment_method_id' => ['required', Rule::exists('payment_methods', 'id')],
            'rates.*.external_id' => ['required'],
            'rates.*.amount' => ['required', 'numeric', 'gte:0'],
            'rates.*.currency' => ['required'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if(request()->get('rates')){

                // check incoming values
                $fetched_values = [];
                foreach (request()->get('rates') as $rate){
                    $value = "{$rate['payment_method_id']}_{$rate['currency']}";
                    if(in_array($value, $fetched_values)){
                        $validator->errors()->add('rates', 'rates.validation.duplicate_item');
                        break;
                    }

                    $fetched_values[] = $value;
                }

                // check database
                $plan = $this->route('plan');

                foreach (request()->get('rates') as $rate){

                    $exists = DB::table('payment_method_plan')
                        ->where('plan_id', '!=', $plan->id)
                        ->where([
                            'payment_method_id' => $rate['payment_method_id'],
                            'currency' => $rate['currency'],
                        ])->exists();

                    if($exists){
                        $validator->errors()->add('rates', 'rates.validation.duplicate_item');
                        break;
                    }
                }
            }
        });
    }
}
