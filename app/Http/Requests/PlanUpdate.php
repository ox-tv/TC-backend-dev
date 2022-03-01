<?php

namespace App\Http\Requests;

use App\Models\Plan;
use App\Models\Pricing;
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
            'is_popular' => ['nullable', 'boolean'],
            'extra_text_content' => ['nullable', 'string'],
            'extra_text_color' => ['nullable', 'string'],
            'description' => ['nullable'],
            'thumbnail' => ['nullable'],
            'pricing.*.id' => ['nullable', Rule::exists('pricing', 'id')],
            'pricing.*.payment_method_id' => ['required', Rule::exists('payment_methods', 'id')],
            'pricing.*.external_id' => ['required'],
            'pricing.*.amount' => ['required', 'numeric', 'gte:0'],
            'pricing.*.currency' => ['required'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if(request()->get('pricing')){

                // check incoming values
                $fetched_values = [];
                foreach (request()->get('pricing') as $pricing){
                    $value = "{$pricing['payment_method_id']}_{$pricing['currency']}";
                    if(in_array($value, $fetched_values)){
                        $validator->errors()->add('pricing', 'pricing.validation.duplicate_item');
                        break;
                    }

                    $fetched_values[] = $value;
                }

                // check database
                /*$plan = $this->route('plan');

                foreach (request()->get('pricing') as $pricing){

                    $exists = Pricing::where('plan_id', '!=', $plan->id)
                        ->where([
                            'payment_method_id' => $pricing['payment_method_id'],
                            'currency' => $pricing['currency'],
                        ])->exists();

                    if($exists){
                        $validator->errors()->add('pricing', 'pricing.validation.duplicate_item');
                        break;
                    }
                }*/
            }
        });
    }
}
